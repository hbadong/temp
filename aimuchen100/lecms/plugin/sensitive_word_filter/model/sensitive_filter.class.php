<?php
/**
 * 敏感词过滤协调器
 *
 * 组合 AC 自动机引擎和词库数据访问层，对外提供统一接口。
 *
 * 职责：
 * 1. 通过 word_repository 获取词库数据
 * 2. 通过 ac_automaton 执行高效检测和替换
 * 3. 对外提供 detect / replace_words / log / clear_cache 接口
 *
 * 所有 SQL 通过 word_repository 完成，由 word_repository 负责 tablepre + addslashes / intval 转义。
 */

if (!defined('ROOT_PATH')) {
    die('Direct access is not allowed.');
}

/**
 * 自动加载依赖类
 */
function _sensitive_filter_autoload()
{
    $base = dirname(__FILE__);
    $paths = [
        $base . '/word_repository.class.php',
        $base . '/ac_automaton.class.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
        }
    }
}
_sensitive_filter_autoload();

class sensitive_filter {

    /** @var int 站点 ID */
    private $site_id;

    /** @var object db_mysql 实例 */
    private $db;

    /** @var string 表前缀 */
    private $tablepre;

    /** @var word_repository 词库数据访问层 */
    private $word_repo;

    /** @var ac_automaton AC 自动机引擎实例 */
    private $ac_engine;

    /**
     * 构造函数
     * @param int   $site_id 站点 ID
     * @param object $db     LECMS db_mysql 实例
     */
    public function __construct($site_id, $db) {
        $this->site_id  = (int)$site_id;
        $this->db       = $db;
        $this->tablepre = $db->tablepre;

        // 初始化词库数据访问层
        $this->word_repo = new word_repository($db);

        // 初始化 AC 自动机并 build 词库
        $this->ac_engine = new ac_automaton();
        $this->rebuild_ac();
    }

    /**
     * 检测敏感词
     * @param string $content 待检测内容
     * @return array 命中词列表（来自 AC 引擎 search）
     */
    public function detect($content) {
        if ($content === '' || $this->ac_engine === null) {
            return array();
        }
        return $this->ac_engine->search($content);
    }

    /**
     * 替换敏感词
     * @param string $content      待处理内容
     * @param string $replace_char 替换字符（AC 引擎内部固定使用 ***，此处保留接口兼容）
     * @return array 包含 text（处理后文本）和 blocked（是否拒绝）
     */
    public function replace_words($content, $replace_char = '*') {
        if ($content === '' || $this->ac_engine === null) {
            return array('text' => $content, 'blocked' => false);
        }

        $result = $this->ac_engine->replace($content);

        // AC 引擎的 replace() 返回 ['text' => ..., 'blocked' => ...]
        // 格式与本接口一致，直接透传
        return $result;
    }

    /**
     * 记录过滤日志
     * @param string $word       命中的敏感词
     * @param int    $content_id 关联内容 ID
     * @param string $action     处理动作：warning/replace/reject
     * @return bool
     */
    public function log($word, $content_id, $action) {
        return $this->word_repo->log_filter($this->site_id, $word, $content_id, $action);
    }

    /**
     * 清除缓存并重建 AC 引擎
     * 词库更新后调用，确保 AC 引擎使用最新词库
     */
    public function clear_cache() {
        $this->rebuild_ac();
    }

    // ============================================================
    // 内部方法
    // ============================================================

    /**
     * 重建 AC 自动机（从词库加载词条并 build）
     */
    private function rebuild_ac() {
        $words = $this->word_repo->get_active_words($this->site_id);

        if ($this->ac_engine === null) {
            $this->ac_engine = new ac_automaton();
        }

        $this->ac_engine->build($words);
    }
}
