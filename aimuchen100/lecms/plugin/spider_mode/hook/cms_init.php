<?php
/**
 * Spider Mode - cms_init Hook（兼容空壳）
 *
 * 重要说明：LECMS 框架全库无 `// hook cms_init.php` 触发点，本文件永远不会被框架执行。
 * 蜘蛛检测逻辑已迁移至 hook/base_control_construct_after.php
 * （合法运行时挂载点，base_control::__construct 末尾执行，$this->db 可用）。
 *
 * 本文件仅保留 spider_mode_run_cms_init_hook() 函数作为兼容入口：
 * - 不再调用全局 db()（LECMS 无该函数，真实环境 fatal error）
 * - 仅执行蜘蛛检测与 IS_SPIDER 定义，不写数据库日志
 * - 日志写入由 base_control_construct_after.php 负责
 *
 * @author     沐尘100
 * @version    1.1.0
 * @cms_version 3.0.0
 */

require_once ROOT_PATH . 'lecms/plugin/spider_mode/model/spider_detect.class.php';

if (!function_exists('spider_mode_run_cms_init_hook')) {
    /**
     * cms_init Hook 兼容入口（蜘蛛检测）
     *
     * @return array ['is_spider' => bool, 'engine' => string|false]
     */
    function spider_mode_run_cms_init_hook()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $engine = SpiderDetect::detect($user_agent);

        if (!defined('IS_SPIDER')) {
            define('IS_SPIDER', $engine !== false);
        }

        return ['is_spider' => $engine !== false, 'engine' => $engine];
    }
}
