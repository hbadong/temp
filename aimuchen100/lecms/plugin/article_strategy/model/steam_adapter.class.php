<?php
/**
 * Steam API 适配器
 * 从 Steam Store API 获取游戏数据并导入到数据库
 * 兼容 PHP 5.4-8.1
 */

class steam_adapter {

    const STEAM_API_APPS = 'https://store.steampowered.com/api/apps';
    const STEAM_API_APPDETAILS = 'https://store.steampowered.com/api/appdetails';
    const STEAM_API_STORESEARCH = 'https://store.steampowered.com/api/storesearch';
    const BATCH_SIZE = 50;
    const REQUEST_TIMEOUT = 30;

    private $site_id = 0;
    private $db;
    private $last_error_code = 0;
    private $last_error_message = '';
    private $imported_count = 0;
    private $skipped_count = 0;

    /**
     * 构造函数
     * @param int $site_id 站点ID
     * @param object $db 数据库实例
     */
    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
    }

    /**
     * 从 Steam API 获取游戏列表
     * @param string $category 分类筛选（Steam genre/category 名称）
     * @param int $limit 获取数量上限（0=不限制）
     * @return array 游戏列表 [{name, tags, category, description, steam_appid}, ...]
     */
    public function fetch_games($category = '', $limit = 0) {
        $games = array();

        if (!empty($category)) {
            // 使用搜索 API 按分类/关键词搜索
            $games = $this->fetch_by_search($category, $limit);
        } else {
            // 获取全部应用列表
            $apps = $this->fetch_app_list();
            if (empty($apps)) {
                return $games;
            }

            // 限制数量
            if ($limit > 0) {
                $apps = array_slice($apps, 0, $limit);
            }

            // 批量获取详情
            $games = $this->fetch_app_details($apps);
        }

        return $games;
    }

    /**
     * 获取 Steam 全部应用列表
     * @return array [{appid, name}, ...]
     */
    private function fetch_app_list() {
        $response = $this->curl_get(self::STEAM_API_APPS);
        if ($response === false) {
            return array();
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['applist']['apps'])) {
            return array();
        }

        $apps = array();
        foreach ($data['applist']['apps'] as $app) {
            if (isset($app['appid']) && isset($app['name'])) {
                $apps[] = array(
                    'appid' => (int)$app['appid'],
                    'name' => trim($app['name']),
                );
            }
        }

        return $apps;
    }

    /**
     * 通过搜索 API 获取游戏
     * @param string $keyword 搜索关键词
     * @param int $limit 数量上限
     * @return array 游戏列表
     */
    private function fetch_by_search($keyword, $limit = 0) {
        $url = self::STEAM_API_STORESEARCH . '?term=' . urlencode($keyword) . '&l=schinese&cc=CN';

        $response = $this->curl_get($url);
        if ($response === false) {
            return array();
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['items'])) {
            return array();
        }

        $games = array();
        $appids = array();

        foreach ($data['items'] as $item) {
            if (isset($item['id']) && isset($item['name'])) {
                $appids[] = (int)$item['id'];
            }
            if ($limit > 0 && count($appids) >= $limit) {
                break;
            }
        }

        if (empty($appids)) {
            return $games;
        }

        // 批量获取详情
        $details = $this->fetch_details_by_ids($appids);
        return $details;
    }

    /**
     * 批量获取应用详情
     * @param array $apps 应用列表 [{appid, name}, ...]
     * @return array 游戏列表
     */
    private function fetch_app_details($apps) {
        $games = array();
        $total = count($apps);

        for ($i = 0; $i < $total; $i += self::BATCH_SIZE) {
            $batch = array_slice($apps, $i, self::BATCH_SIZE);
            $appids = array();
            foreach ($batch as $app) {
                $appids[] = $app['appid'];
            }

            $details = $this->fetch_details_by_ids($appids);
            $games = array_merge($games, $details);
        }

        return $games;
    }

    /**
     * 根据 appid 列表获取详情
     * @param array $appids appid 列表
     * @return array 游戏列表
     */
    private function fetch_details_by_ids($appids) {
        $ids = implode(',', $appids);
        $url = self::STEAM_API_APPDETAILS . '?appids=' . $ids . '&l=schinese&cc=CN';

        $response = $this->curl_get($url);
        if ($response === false) {
            return array();
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return array();
        }

        $games = array();
        foreach ($data as $appid => $item) {
            if (!is_array($item) || !isset($item['success']) || $item['success'] !== true) {
                continue;
            }

            $game = $this->parse_app_details($item);
            if ($game !== null) {
                $games[] = $game;
            }
        }

        return $games;
    }

    /**
     * 解析单个应用详情
     * @param array $item Steam API 返回的单条应用数据
     * @return array|null 标准化游戏数据
     */
    private function parse_app_details($item) {
        $data = isset($item['data']) ? $item['data'] : array();

        $name = isset($data['name']) ? trim($data['name']) : '';
        if ($name === '') {
            return null;
        }

        // 解析标签/分类
        $tags = array();
        $genres = array();
        if (isset($data['genres']) && is_array($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                if (isset($genre['description'])) {
                    $genres[] = trim($genre['description']);
                }
            }
        }
        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $cat) {
                if (isset($cat['description'])) {
                    $tags[] = trim($cat['description']);
                }
            }
        }

        // 合并标签和分类
        $all_tags = array_unique(array_merge($genres, $tags));
        $primary_category = !empty($genres) ? $genres[0] : (!empty($tags) ? $tags[0] : '');

        $description = isset($data['short_description']) ? trim($data['short_description']) : '';
        if (mb_strlen($description) > 500) {
            $description = mb_substr($description, 0, 500) . '...';
        }

        $appid = isset($item['appid']) ? (int)$item['appid'] : 0;

        return array(
            'name' => $name,
            'tags' => implode(',', $all_tags),
            'category' => $primary_category,
            'description' => $description,
            'steam_appid' => $appid,
            'platform' => $this->detect_platform($data),
        );
    }

    /**
     * 检测游戏平台
     * @param array $data Steam 应用数据
     * @return string 平台标识
     */
    private function detect_platform($data) {
        $platforms = array();
        if (isset($data['platforms'])) {
            if (isset($data['platforms']['windows']) && $data['platforms']['windows']) $platforms[] = 'windows';
            if (isset($data['platforms']['mac']) && $data['platforms']['mac']) $platforms[] = 'mac';
            if (isset($data['platforms']['linux']) && $data['platforms']['linux']) $platforms[] = 'linux';
        }
        return implode(',', $platforms);
    }

    /**
     * 数据清洗：去重、分类映射
     * @param array $games 原始游戏列表
     * @return array 清洗后的游戏列表
     */
    public function clean_data($games) {
        if (empty($games)) {
            return array();
        }

        $site_id = $this->site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 收集所有游戏名称用于去重检查
        $names = array();
        foreach ($games as $game) {
            $names[] = $game['name'];
        }

        // 查询数据库中已存在的游戏（按站点隔离）
        $existing_names = array();
        if (!empty($names)) {
            $name_list = array();
            foreach ($names as $n) {
                $name_list[] = "'" . addslashes($n) . "'";
            }
            $in = implode(',', $name_list);
            $query = $this->db->query("
                SELECT name FROM `{$tablepre}cms_game`
                WHERE site_id = '{$site_id}' AND name IN ({$in})
            ");
            if ($query) {
                while ($row = mysqli_fetch_assoc($query)) {
                    $existing_names[] = $row['name'];
                }
            }
        }

        // 去重 + 分类映射
        $seen = array();
        $result = array();
        $category_map = $this->get_category_map($site_id);

        foreach ($games as $game) {
            // 跳过数据库中已存在的
            if (in_array($game['name'], $existing_names)) {
                continue;
            }

            // 内存去重
            if (isset($seen[$game['name']])) {
                continue;
            }
            $seen[$game['name']] = true;

            // 分类映射
            $category_id = 0;
            if (!empty($game['category'])) {
                $cat_name = $game['category'];
                if (isset($category_map[$cat_name])) {
                    $category_id = $category_map[$cat_name];
                } else {
                    // 自动创建分类
                    $category_id = $this->ensure_category($site_id, $cat_name);
                    $category_map[$cat_name] = $category_id;
                }
            }

            $game['category_id'] = $category_id;
            $result[] = $game;
        }

        return $result;
    }

    /**
     * 获取分类名称到ID的映射
     * @param int $site_id 站点ID
     * @return array [category_name => category_id, ...]
     */
    private function get_category_map($site_id) {
        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $map = array();
        $query = $this->db->query("
            SELECT id, name FROM `{$tablepre}cms_category`
            WHERE site_id = '{$site_id}' AND is_active = 1
        ");
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $map[$row['name']] = (int)$row['id'];
            }
        }

        return $map;
    }

    /**
     * 确保分类存在，不存在则创建
     * @param int $site_id 站点ID
     * @param string $name 分类名称
     * @return int 分类ID
     */
    private function ensure_category($site_id, $name) {
        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 先查询
        $row = $this->db->fetch_first("
            SELECT id FROM `{$tablepre}cms_category`
            WHERE site_id = '{$site_id}' AND name = '" . addslashes($name) . "'
            LIMIT 1
        ");

        if ($row) {
            return (int)$row['id'];
        }

        // 创建分类
        $slug = $this->generate_slug($name);
        $now = date('Y-m-d H:i:s');
        $this->db->query("
            INSERT INTO `{$tablepre}cms_category`
            (`site_id`, `name`, `slug`, `is_active`, `created_at`)
            VALUES ('{$site_id}', '" . addslashes($name) . "', '" . addslashes($slug) . "', 1, '{$now}')
        ");

        return (int)$this->db->insert_id();
    }

    /**
     * 生成 URL 别名
     * @param string $name 名称
     * @return string URL 友好的别名
     */
    private function generate_slug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/[^\x{4e00}-\x{9fa5}a-z0-9]+/u', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * 导入游戏数据到数据库
     * @param array $games 清洗后的游戏列表
     * @return array 导入报告
     */
    public function import($games) {
        $this->imported_count = 0;
        $this->skipped_count = 0;

        $games = $this->clean_data($games);
        if (empty($games)) {
            return array(
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('No new games to import'),
            );
        }

        $site_id = $this->site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $now = date('Y-m-d H:i:s');
        $errors = array();

        foreach ($games as $game) {
            $name = $game['name'];
            $tags = isset($game['tags']) ? $game['tags'] : '';
            $category_id = isset($game['category_id']) ? (int)$game['category_id'] : 0;
            $description = isset($game['description']) ? $game['description'] : '';
            $platform = isset($game['platform']) ? $game['platform'] : '';
            $steam_appid = isset($game['steam_appid']) ? (int)$game['steam_appid'] : 0;

            $result = $this->db->query("
                INSERT INTO `{$tablepre}cms_game`
                (`site_id`, `name`, `category_id`, `description`, `cover`, `tags`, `platform`, `created_at`)
                VALUES (
                    '{$site_id}',
                    '" . addslashes($name) . "',
                    '{$category_id}',
                    '" . addslashes($description) . "',
                    '',
                    '" . addslashes($tags) . "',
                    '" . addslashes($platform) . "',
                    '{$now}'
                )
            ");

            if ($result) {
                $this->imported_count++;
            } else {
                $this->skipped_count++;
                $errors[] = 'Failed to insert: ' . $name;
            }
        }

        return array(
            'total' => count($games),
            'imported' => $this->imported_count,
            'skipped' => $this->skipped_count,
            'errors' => $errors,
        );
    }

    /**
     * cURL GET 请求（兼容 ai_api_adapter 模式）
     * @param string $url 请求 URL
     * @return string|false 响应内容或 false
     */
    private function curl_get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $response = curl_exec($ch);

        if ($response === false) {
            $this->last_error_code = 0;
            $this->last_error_message = curl_error($ch);
            curl_close($ch);
            return false;
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_error_code = $http_code;
        curl_close($ch);

        if ($http_code == 200) {
            return $response;
        }

        $this->last_error_message = 'HTTP error: ' . $http_code;
        return false;
    }

    /**
     * 获取最后错误码
     * @return int
     */
    public function get_last_error_code() {
        return $this->last_error_code;
    }

    /**
     * 获取最后错误信息
     * @return string
     */
    public function get_last_error_message() {
        return $this->last_error_message;
    }

    /**
     * 获取导入数量
     * @return int
     */
    public function get_imported_count() {
        return $this->imported_count;
    }

    /**
     * 获取跳过数量
     * @return int
     */
    public function get_skipped_count() {
        return $this->skipped_count;
    }
}
