<?php
/**
 * 起名系统移动端控制器
 */

defined('IN_YZMPHP') or exit('Access Den Den');

class mobile {
    
    /**
     * 构造函数
     */
    public function __construct() {
        // 检测是否为移动端访问
        $this->check_mobile();
    }
    
    /**
     * 检测移动端访问
     */
    private function check_mobile() {
        // 如果已经明确指定了移动端参数，则使用移动端模板
        if (isset($_GET['mobile']) || isset($_GET['m']) && $_GET['m'] === 'mobile') {
            return true;
        }
        
        // 通过User-Agent检测移动端
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $mobile_agents = array(
            'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'Windows Phone', 'MIDP', 'SymbianOS', 'iOS', 'Windows CE',
            'Opera Mini', 'UCWEB', 'Mobile', 'Opera Mobi'
        );
        
        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 获取移动端模板路径
     */
    public function get_mobile_template($template) {
        return APP_PATH . 'qiming/view/default/' . $template . '_mobile.html';
    }
}
