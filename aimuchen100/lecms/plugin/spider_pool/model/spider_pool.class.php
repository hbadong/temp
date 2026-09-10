<?php
/**
 * 蜘蛛池域名管理器（SpiderPool）。
 *
 * 使用 LECMS 真实数据库接口 query()/fetch_first()/fetch_all()，不依赖全局
 * db() 函数。域名只保存为字符串，不检查外部域名的可达性。
 */
class spider_pool
{
    /** @var spider_pool|null */
    private static $instance = null;

    /** @var object|null */
    private $db;

    /** @var int 当前管理上下文的站点 ID，用于 delete/toggle 隔离 */
    private $site_id = 0;

    /**
     * @param object|int|null $db_or_site_id 数据库对象，或兼容旧调用的站点 ID
     * @param object|int|null $site_id_or_db 站点 ID，或数据库对象
     */
    public function __construct($db_or_site_id = null, $site_id_or_db = 0)
    {
        // 同时兼容 new spider_pool($db, $site_id) 和
        // new spider_pool($site_id, $db) 两种注入顺序。
        if (is_object($db_or_site_id)) {
            $this->db = $db_or_site_id;
            if (!is_object($site_id_or_db)) {
                $this->site_id = (int)$site_id_or_db;
            }
        } else {
            $this->site_id = (int)$db_or_site_id;
            if (is_object($site_id_or_db)) {
                $this->db = $site_id_or_db;
            }
        }
    }

    /**
     * 获取单例实例。
     *
     * spider-mode hook 使用 spider_pool::instance()->get_active()，因此
     * instance() 和 get_active() 均允许不传站点参数。
     *
     * @param object|null $db 数据库注入
     * @param int $site_id delete/toggle 的站点上下文
     * @return spider_pool
     */
    public static function instance($db = null, $site_id = 0)
    {
        if (self::$instance === null) {
            self::$instance = new self($db, $site_id);
        }
        return self::$instance;
    }

    /**
     * 获取数据库对象。
     *
     * @return object
     * @throws RuntimeException 未注入数据库时
     */
    private function get_db()
    {
        if ($this->db === null && isset($GLOBALS['run']) && is_object($GLOBALS['run'])) {
            // 前台控制器会把自身放到 $GLOBALS['run']；访问 ->db 会走
            // LECMS control::__get()，这样 shutdown hook 的无参 instance()
            // 仍可使用当前请求数据库连接。
            try {
                $controller_db = $GLOBALS['run']->db;
                if (is_object($controller_db)) {
                    $this->db = $controller_db;
                }
            } catch (Throwable $e) {
                // 继续走统一的注入异常，不能让数据库解析错误泄漏到页面。
            }
        }

        if ($this->db === null) {
            throw new RuntimeException('spider_pool 需要注入 db 对象：new spider_pool($db, $site_id)');
        }
        return $this->db;
    }

    /**
     * 添加池化域名。外部域名不做可达性校验。
     *
     * @return bool
     */
    public function add($site_id, $domain, $sort = 0)
    {
        $db = $this->get_db();
        $sql = "INSERT INTO `{$db->tablepre}spider_pool` (`site_id`, `domain`, `status`, `sort`, `dateline`) VALUES ("
            . intval($site_id) . ", '" . addslashes((string)$domain) . "', 1, "
            . intval($sort) . ", " . intval(isset($_ENV['_time']) ? $_ENV['_time'] : time()) . ")";
        return (bool)$db->query($sql);
    }

    /**
     * 删除当前站点的域名记录，避免跨站点越权。
     *
     * @return bool
     */
    public function delete($id)
    {
        $db = $this->get_db();
        $sql = "DELETE FROM `{$db->tablepre}spider_pool` WHERE `id` = " . intval($id)
            . " AND `site_id` = " . intval($this->site_id);
        return (bool)$db->query($sql);
    }

    /**
     * 翻转当前站点域名的启用状态，不修改其他字段。
     *
     * @return bool
     */
    public function toggle($id)
    {
        $db = $this->get_db();
        $sql = "UPDATE `{$db->tablepre}spider_pool` SET `status` = 1 - `status` WHERE `id` = "
            . intval($id) . " AND `site_id` = " . intval($this->site_id);
        return (bool)$db->query($sql);
    }

    /**
     * 更新当前站点的域名记录（domain + sort）。
     *
     * 写入限制在当前 site_id，避免跨站点越权。
     *
     * @param int    $id     记录 ID
     * @param string $domain 新域名
     * @param int    $sort   新排序值
     * @return bool
     */
    public function update($id, $domain, $sort = 0)
    {
        $db = $this->get_db();
        $sql = "UPDATE `{$db->tablepre}spider_pool` SET `domain` = '" . addslashes((string)$domain) . "', "
            . "`sort` = " . intval($sort) . " WHERE `id` = " . intval($id)
            . " AND `site_id` = " . intval($this->site_id);
        return (bool)$db->query($sql);
    }

    /**
     * 批量更新排序字段（id => sort）。
     *
     * 入参非法（非数组/空）或更新失败返回 false；至少一条成功返回 true。
     * 写入限制在当前 site_id，越权记录被静默忽略。
     *
     * @param array $id_sort_pairs id => sort 映射
     * @return bool
     */
    public function sort_batch($id_sort_pairs)
    {
        if (!is_array($id_sort_pairs) || empty($id_sort_pairs)) {
            return false;
        }
        $db = $this->get_db();
        $site_id = intval($this->site_id);
        $any_success = false;
        foreach ($id_sort_pairs as $id => $sort) {
            $id = intval($id);
            if ($id <= 0) {
                continue;
            }
            $sql = "UPDATE `{$db->tablepre}spider_pool` SET `sort` = " . intval($sort)
                . " WHERE `id` = " . $id . " AND `site_id` = " . $site_id;
            if ($db->query($sql)) {
                $any_success = true;
            }
        }
        return $any_success;
    }

    /**
     * 获取站点的全部域名记录，包括停用项。
     *
     * @return array
     */
    public function get_list($site_id)
    {
        $db = $this->get_db();
        $sql = "SELECT * FROM `{$db->tablepre}spider_pool` WHERE `site_id` = "
            . intval($site_id) . " ORDER BY `sort` ASC, `id` ASC";
        return $db->fetch_all($sql);
    }

    /**
     * 获取活跃域名字符串列表。
     *
     * site_id 为 null 时不按站点过滤，用于 spider-mode hook 注入所有站点
     * 的活跃链接；传入站点 ID 时执行严格的多站点隔离。
     *
     * @return array
     */
    public function get_active($site_id = null)
    {
        $db = $this->get_db();
        $sql = "SELECT `domain` FROM `{$db->tablepre}spider_pool` WHERE `status` = 1";
        if ($site_id !== null) {
            $sql .= " AND `site_id` = " . intval($site_id);
        }
        $sql .= " ORDER BY `sort` ASC, `id` ASC";
        $rows = $db->fetch_all($sql);

        $domains = [];
        foreach ((array)$rows as $row) {
            if (is_array($row) && isset($row['domain'])) {
                $domains[] = $row['domain'];
            } elseif (is_string($row)) {
                $domains[] = $row;
            }
        }
        return $domains;
    }

    /**
     * 生成蜘蛛池链接 HTML（隐藏 div 包裹）。
     *
     * 实现为静态方法而非全局函数：hook 文件被 process_hook() 内联进
     * base_control::__construct() 方法体，方法内定义全局函数会触发
     * "Cannot redeclare" fatal error。
     *
     * @param array|false $domains 域名字符串数组（或 get_active 失败时的 false）
     * @return string 域名为空时返回空字符串
     */
    public static function render_links($domains)
    {
        if (empty($domains) || !is_array($domains)) {
            return '';
        }

        $links = '';
        foreach ($domains as $domain) {
            // 兼容直接传入 fetch_all 结果行的场景
            if (is_array($domain)) {
                $domain = isset($domain['domain']) ? $domain['domain'] : '';
            }
            $domain = trim((string)$domain);
            if ($domain === '') {
                continue;
            }
            // ENT_QUOTES：同时转义单双引号，防止域名内容逃逸 href 属性
            $safe = htmlspecialchars($domain, ENT_QUOTES);
            $links .= '<a href="http://' . $safe . '" target="_blank">' . $safe . '</a>';
        }

        if ($links === '') {
            return '';
        }
        return '<div style="display:none">' . $links . '</div>';
    }

    /**
     * 把蜘蛛池链接注入到 HTML 的 </body> 之前。
     *
     * 蜘蛛池为空或页面无 </body> 时原样返回，不修改输出。
     *
     * @param string $html
     * @param array|false $domains
     * @return string
     */
    public static function inject_links($html, $domains)
    {
        $links_html = self::render_links($domains);
        if ($links_html === '') {
            return $html;
        }
        return str_ireplace('</body>', $links_html . '</body>', (string)$html);
    }

    /**
     * 从站点活跃域名中随机抽取最多 count 个。
     *
     * @return array
     */
    public function get_random($site_id, $count)
    {
        $count = intval($count);
        if ($count <= 0) {
            return [];
        }

        $domains = $this->get_active($site_id);
        if (count($domains) <= $count) {
            return $domains;
        }

        shuffle($domains);
        return array_slice($domains, 0, $count);
    }
}
