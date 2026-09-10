<?php
/**
 * AccessMode - 访问模式管理器
 *
 * 管理蜘蛛模式配置（normal / spider_only / icp_filing），
 * 支持模式查询、蜘蛛模式状态检测、模式切换、启用/禁用及操作日志记录。
 *
 * 数据表：pre_spider_mode（site_id, mode, spider_mode_active, updated_at）
 * 日志表：pre_access_mode_log（site_id, old_mode, new_mode, uid, ip, dateline）
 *
 * 框架兼容性说明：
 * - LECMS 无全局 db() 函数，db 对象通过构造函数注入（真实环境传入 $this->db）
 * - db 接口为 query($sql)/fetch_first($sql)/fetch_all($sql)，无 fetch()/insert() 参数绑定
 * - 表名使用 $db->tablepre 前缀拼接，值用 intval()/addslashes() 转义
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

class AccessMode
{
    /**
     * @var int 站点 ID
     */
    private $site_id;

    /**
     * @var object|null 数据库对象（真实环境注入 $this->db）
     */
    private $db;

    /**
     * Runtime 缓存：避免重复查询数据库
     * @var array|null
     */
    private static $runtime_cache = [];

    /**
     * 允许的模式值
     */
    const VALID_MODES = ['normal', 'spider_only', 'icp_filing'];

    /**
     * 构造函数
     *
     * @param   int     $site_id  站点 ID（多站点隔离）
     * @param   object  $db       数据库对象（LECMS 的 db_mysql 实例，如 $this->db）
     */
    public function __construct($site_id, $db = null)
    {
        $this->site_id = (int)$site_id;
        $this->db = $db;
    }

    /**
     * 获取数据库对象
     *
     * @return  object
     * @throws  RuntimeException 未注入 db 对象时
     */
    private function get_db()
    {
        if ($this->db === null) {
            throw new RuntimeException('AccessMode 需要注入 db 对象：new AccessMode($site_id, $this->db)');
        }
        return $this->db;
    }

    /**
     * 获取当前访问模式
     *
     * 优先返回 runtime 缓存；缓存未命中时查询数据库，
     * 数据库中无记录时默认返回 'normal'。
     *
     * @return  string  模式值（normal / spider_only / icp_filing）
     */
    public function get_mode()
    {
        // 优先 runtime 缓存
        if (isset(self::$runtime_cache[$this->site_id])) {
            return self::$runtime_cache[$this->site_id]['mode'];
        }

        // 查询数据库（真实 db 无参数绑定，site_id 用 intval 转义）
        $db = $this->get_db();
        $sql = "SELECT `mode`, `spider_mode_active` FROM `{$db->tablepre}spider_mode` WHERE `site_id` = " . intval($this->site_id) . " LIMIT 1";
        $row = $db->fetch_first($sql);

        if ($row && isset($row['mode'])) {
            $mode = $row['mode'];
        } else {
            $mode = 'normal';
        }

        // 写入 runtime 缓存
        self::$runtime_cache[$this->site_id] = [
            'mode'              => $mode,
            'spider_mode_active' => isset($row['spider_mode_active']) ? (int)$row['spider_mode_active'] : 0,
        ];

        return $mode;
    }

    /**
     * 是否启用了蜘蛛模式（spider_mode_active = 1）
     *
     * @return  bool
     */
    public function is_active()
    {
        // 确保缓存已加载
        $this->get_mode();

        $cached = isset(self::$runtime_cache[$this->site_id])
            ? self::$runtime_cache[$this->site_id]
            : ['spider_mode_active' => 0];

        return (bool)$cached['spider_mode_active'];
    }

    /**
     * 切换访问模式
     *
     * 更新 pre_spider_mode 表的 mode 字段，
     * 并向 pre_access_mode_log 写入操作日志。
     *
     * @param   string  $new_mode  新模式（normal / spider_only / icp_filing）
     * @param   int     $uid       操作者 UID
     * @param   string  $ip        操作者 IP
     * @return  bool               切换成功返回 true，非法模式返回 false
     */
    public function switch_mode($new_mode, $uid, $ip)
    {
        // 校验模式合法性
        if (!in_array($new_mode, self::VALID_MODES, true)) {
            return false;
        }

        $uid  = (int)$uid;
        $ip   = (string)$ip;
        $site_id = $this->site_id;

        // 获取旧模式（确保缓存存在）
        $old_mode = $this->get_mode();

        // 相同模式无需切换
        if ($old_mode === $new_mode) {
            return true;
        }

        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
        $db = $this->get_db();

        // 更新 pre_spider_mode 表（真实 db 无参数绑定，值用 addslashes/intval 转义）
        $update_sql = "UPDATE `{$db->tablepre}spider_mode` SET `mode` = '" . addslashes($new_mode)
            . "', `updated_at` = " . intval($dateline) . " WHERE `site_id` = " . intval($site_id);
        $db->query($update_sql);

        // 记录操作日志到 pre_access_mode_log（db 无 insert() 方法，使用 query() INSERT）
        $log_sql = "INSERT INTO `{$db->tablepre}access_mode_log` (`site_id`, `old_mode`, `new_mode`, `uid`, `ip`, `dateline`) VALUES ("
            . intval($site_id) . ", '" . addslashes($old_mode) . "', '" . addslashes($new_mode) . "', "
            . intval($uid) . ", '" . addslashes($ip) . "', " . intval($dateline) . ")";
        $db->query($log_sql);

        // 更新 runtime 缓存
        self::$runtime_cache[$site_id]['mode'] = $new_mode;

        return true;
    }

    /**
     * 启用/禁用蜘蛛模式
     *
     * 更新 pre_spider_mode 表的 spider_mode_active 字段，
     * 并同步刷新 runtime 缓存使 is_active() 立即生效。
     *
     * @param   int|bool  $active  1/true 启用，0/false 禁用
     * @return  bool               操作成功返回 true
     */
    public function switch_active($active)
    {
        $active  = $active ? 1 : 0;
        $site_id = $this->site_id;
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();

        $db = $this->get_db();
        $update_sql = "UPDATE `{$db->tablepre}spider_mode` SET `spider_mode_active` = " . intval($active)
            . ", `updated_at` = " . intval($dateline) . " WHERE `site_id` = " . intval($site_id);
        $db->query($update_sql);

        // 确保缓存已加载后刷新 active 状态
        $this->get_mode();
        self::$runtime_cache[$site_id]['spider_mode_active'] = $active;

        return true;
    }

    /**
     * 双面渲染：根据访问模式返回不同内容
     *
     * 使用 ob_start() 缓冲输出，根据当前模式和访问者类型（蜘蛛/人工）
     * 返回不同的页面内容：
     * - normal 模式：无论蜘蛛/人工均返回完整内容
     * - spider_only 模式：蜘蛛返回完整内容，人工返回受限页（body 替换为受限版）
     * - icp_filing 模式：蜘蛛返回完整内容，人工返回 ICP 备案页（body 替换为备案信息）
     *
     * 当 spider_mode_active = 0 时，无论模式如何，均返回完整内容。
     *
     * @param   callable  $content_callback  内容生成回调，返回原始 HTML 内容
     * @return  string  处理后的页面内容
     */
    public function render($content_callback)
    {
        if (!is_callable($content_callback)) {
            return '';
        }

        $mode = $this->get_mode();
        $is_spider = defined('IS_SPIDER') ? (bool)IS_SPIDER : false;

        // 使用 ob_start() 缓冲输出
        ob_start();
        $content = $content_callback();
        ob_end_clean();

        // spider_mode_active = 0 或 normal 模式：直接返回完整内容
        if (!$this->is_active() || $mode === 'normal') {
            return $content;
        }

        // 蜘蛛访问：直接返回完整内容
        if ($is_spider) {
            return $content;
        }

        // 人工访问：根据模式整体替换 body 块（含开闭标签）
        // 修复 II-3：原实现仅替换 <body> 开标签，body 内原内容残留 → 人工 view-source
        // 仍能看到完整正文，构成内容泄露。改为整体替换 <body>...</body>
        // 块，`/s` 修饰符让 . 匹配换行。
        if ($mode === 'spider_only') {
            $limited_body = '<body><div class="spider-mode-limited">Limited Content</div></body>';
            return preg_replace('/<body[^>]*>.*<\/body>/is', $limited_body, $content, 1);
        }

        if ($mode === 'icp_filing') {
            $icp_body = '<body><div class="icp-filing-page">ICP 备案信息</div></body>';
            return preg_replace('/<body[^>]*>.*<\/body>/is', $icp_body, $content, 1);
        }

        return $content;
    }
}
