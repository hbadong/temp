<?php
/**
 * CSV 游戏分类导入器
 * 解析 CSV 文件并导入游戏分类数据
 * CSV 格式: name, category, tags, description
 * 兼容 PHP 5.4-8.1
 */

class csv_importer {

    const BATCH_SIZE = 1000;
    public $db;

    /**
     * 解析 CSV 文件
     * @param string $file_path 文件路径
     * @return array 解析结果 ['games' => [], 'total' => int, 'format' => 'csv']
     */
    public function parse($file_path) {
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return array('games' => array(), 'total' => 0, 'format' => 'csv', 'error' => 'Cannot open file');
        }

        // 读取表头
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return array('games' => array(), 'total' => 0, 'format' => 'csv', 'error' => 'Empty file or invalid CSV');
        }

        // 标准化表头（小写，去BOM，去空格）
        $header = array_map(function($col) {
            $col = trim($col);
            // 去除 UTF-8 BOM
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return strtolower($col);
        }, $header);

        // 映射列索引
        $col_map = array();
        $valid_cols = array('name', 'category', 'tags', 'description');
        foreach ($header as $index => $col) {
            if (in_array($col, $valid_cols)) {
                $col_map[$col] = $index;
            }
        }

        // name 列为必填
        if (!isset($col_map['name'])) {
            fclose($handle);
            return array('games' => array(), 'total' => 0, 'format' => 'csv', 'error' => 'Missing required column: name');
        }

        $games = array();
        $row_num = 0;
        $parse_errors = array();

        while (($row = fgetcsv($handle)) !== false) {
            $row_num++;

            // 跳过空行
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            // 补齐列数
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            }

            // 提取 name（必填）
            $name = isset($row[$col_map['name']]) ? trim($row[$col_map['name']]) : '';
            if ($name === '') {
                $parse_errors[] = "Row {$row_num}: name is empty, skipped";
                continue;
            }

            // 提取其他字段
            $category = isset($col_map['category']) ? trim($row[$col_map['category']]) : '';
            $tags = isset($col_map['tags']) ? trim($row[$col_map['tags']]) : '';
            $description = isset($col_map['description']) ? trim($row[$col_map['description']]) : '';

            $games[] = array(
                'name' => $name,
                'category' => $category,
                'tags' => $tags,
                'description' => $description,
            );
        }

        fclose($handle);

        $result = array(
            'games' => $games,
            'total' => count($games),
            'format' => 'csv',
        );

        if (!empty($parse_errors)) {
            $result['parse_errors'] = $parse_errors;
        }

        return $result;
    }

    /**
     * 验证单行数据
     * @param array $row 数据行
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validate($row) {
        $errors = array();

        if (empty($row['name'])) {
            $errors[] = 'name is required';
        }

        if (isset($row['name']) && mb_strlen($row['name']) > 200) {
            $errors[] = 'name exceeds 200 characters';
        }

        if (isset($row['category']) && mb_strlen($row['category']) > 100) {
            $errors[] = 'category exceeds 100 characters';
        }

        if (isset($row['tags']) && mb_strlen($row['tags']) > 500) {
            $errors[] = 'tags exceeds 500 characters';
        }

        if (isset($row['description']) && mb_strlen($row['description']) > 2000) {
            $errors[] = 'description exceeds 2000 characters';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * 批量验证游戏数据
     * @param array $games 游戏列表
     * @return array 验证报告 ['valid' => [], 'invalid' => []]
     */
    public function validate_batch($games) {
        $valid = array();
        $invalid = array();

        foreach ($games as $index => $game) {
            $result = $this->validate($game);
            if ($result['valid']) {
                $valid[] = $game;
            } else {
                $invalid[] = array(
                    'row' => $index + 1,
                    'data' => $game,
                    'errors' => $result['errors'],
                );
            }
        }

        return array(
            'valid' => $valid,
            'invalid' => $invalid,
        );
    }

    /**
     * 分批获取游戏（每批1000条）
     * @param array $games 全部游戏数组
     * @return array 分批后的数组
     */
    public function batch($games) {
        $batches = array();
        $total = count($games);
        for ($i = 0; $i < $total; $i += self::BATCH_SIZE) {
            $batches[] = array_slice($games, $i, self::BATCH_SIZE);
        }
        return $batches;
    }

    /**
     * 获取每批大小
     * @return int
     */
    public function get_batch_size() {
        return self::BATCH_SIZE;
    }

    /**
     * 导入游戏数据到数据库
     * @param int $site_id 站点ID
     * @param array $games 游戏列表
     * @return array 导入报告
     */
    public function import_to_db($site_id, $games) {
        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 先验证
        $validation = $this->validate_batch($games);
        $games = $validation['valid'];

        // 获取已有名称用于去重（PHP 5.4 兼容，不用 array_column）
        $names = array();
        foreach ($games as $g) {
            $names[] = $g['name'];
        }
        $existing_names = $this->get_existing_names($site_id, $names);

        // 获取分类映射
        $category_map = $this->get_category_map($site_id);

        $imported = 0;
        $skipped = 0;
        $errors = array();
        $now = date('Y-m-d H:i:s');

        foreach ($games as $game) {
            // 去重检查
            if (in_array($game['name'], $existing_names)) {
                $skipped++;
                continue;
            }

            // 确保分类存在
            $category_id = 0;
            if (!empty($game['category'])) {
                $cat_name = $game['category'];
                if (isset($category_map[$cat_name])) {
                    $category_id = $category_map[$cat_name];
                } else {
                    $category_id = $this->ensure_category($site_id, $cat_name);
                    $category_map[$cat_name] = $category_id;
                }
            }

            // 插入数据库
            $result = $this->db->query("
                INSERT INTO `{$tablepre}cms_game`
                (`site_id`, `name`, `category_id`, `description`, `cover`, `tags`, `platform`, `created_at`)
                VALUES (
                    '{$site_id}',
                    '" . addslashes($game['name']) . "',
                    '{$category_id}',
                    '" . addslashes($game['description']) . "',
                    '',
                    '" . addslashes($game['tags']) . "',
                    '',
                    '{$now}'
                )
            ");

            if ($result) {
                $imported++;
                $existing_names[] = $game['name'];
            } else {
                $skipped++;
                $errors[] = 'Failed to insert: ' . $game['name'];
            }
        }

        return array(
            'total' => count($games) + $skipped + count($validation['invalid']),
            'imported' => $imported,
            'skipped' => $skipped + count($validation['invalid']),
            'invalid_rows' => $validation['invalid'],
            'errors' => $errors,
        );
    }

    /**
     * 获取已存在的游戏名称列表
     * @param int $site_id 站点ID
     * @param array $names 待检查名称列表
     * @return array 已存在的名称列表
     */
    private function get_existing_names($site_id, $names) {
        if (empty($names)) {
            return array();
        }

        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $existing = array();
        $batches = array_chunk($names, 1000);

        foreach ($batches as $batch) {
            $name_list = array();
            foreach ($batch as $n) {
                $name_list[] = "'" . addslashes($n) . "'";
            }
            $in = implode(',', $name_list);
            $query = $this->db->query("
                SELECT name FROM `{$tablepre}cms_game`
                WHERE site_id = '{$site_id}' AND name IN ({$in})
            ");
            if ($query) {
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $existing[] = $row['name'];
                }
            }
        }

        return $existing;
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
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
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

        return (int)$this->db->last_insert_id();
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
}
