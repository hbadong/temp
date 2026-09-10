<?php
defined('ROOT_PATH') or exit;
require_once ROOT_PATH . 'lecms/plugin/seo_tools/model/seo_verification.class.php';

/**
 * SEO 验证文件后台管理控制器。
 *
 * 控制器显式加载 seo_verification 模型，因为模型文件名不符合 LECMS
 * core::model() 的自动加载约定；require 使用 ROOT_PATH 兼容 runcache 编译。
 */
require_once ROOT_PATH . 'lecms/plugin/seo_tools/model/seo_verification.class.php';

class verification_control extends admin_control
{
    /**
     * 获取后台当前站点 ID：GET/POST → CURRENT_SITE_ID → 首条验证记录。
     */
    private function get_site_id()
    {
        if (isset($_GET['site_id']) && (int)$_GET['site_id'] > 0) {
            return (int)$_GET['site_id'];
        }
        if (isset($_POST['site_id']) && (int)$_POST['site_id'] > 0) {
            return (int)$_POST['site_id'];
        }
        if (defined('CURRENT_SITE_ID') && (int)CURRENT_SITE_ID > 0) {
            return (int)CURRENT_SITE_ID;
        }

        $db = $this->db;
        if (!is_object($db)) {
            return 0;
        }
        $row = $db->fetch_first("SELECT `site_id` FROM `{$db->tablepre}seo_verification` WHERE `site_id` > 0 ORDER BY `site_id` ASC LIMIT 1");
        return ($row && !empty($row['site_id'])) ? (int)$row['site_id'] : 0;
    }

    /**
     * 最小后台访问校验：要求登录，并拒绝明确标记为非管理员/无权限的账号。
     */
    private function is_authorized()
    {
        if (!isset($this->_uid) || (int)$this->_uid <= 0) {
            return false;
        }
        if (isset($this->_user) && is_array($this->_user)) {
            if (isset($this->_user['uid']) && (int)$this->_user['uid'] <= 0) {
                return false;
            }
            if (isset($this->_user['is_admin']) && !$this->_user['is_admin']) {
                return false;
            }
            if (isset($this->_user['admin']) && !$this->_user['admin']) {
                return false;
            }
            if (isset($this->_user['seo_tools_permission']) && !$this->_user['seo_tools_permission']) {
                return false;
            }
        }
        return true;
    }

    /**
     * 拒绝未授权后台请求并跳转。
     */
    private function require_authorized()
    {
        if ($this->is_authorized()) {
            return true;
        }
        $this->message(1, '无权访问验证文件管理', '?index-index');
        return false;
    }

    /**
     * 验证文件管理首页。
     */
    public function index()
    {
        if (!$this->require_authorized()) {
            return false;
        }

        $site_id = $this->get_site_id();
        $model = new seo_verification($site_id, $this->db);
        $list = $model->get_list($site_id);

        $this->assign('list', $list);
        $this->assign_value('site_id', $site_id);
        $this->assign_value('form_hash', form_hash());
        $this->display('verification_index.htm');
        return true;
    }

    /**
     * 保存后台上传的验证文件（POST）。
     */
    public function save()
    {
        if (!$this->require_authorized()) {
            return false;
        }
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = $this->get_site_id();
        $engine = trim((string)R('engine', 'P'));
        $filename = trim((string)R('filename', 'P'));
        $content = (string)R('content', 'P');

        $model = new seo_verification($site_id, $this->db);
        if (!$model->save($site_id, $engine, $filename, $content)) {
            E(1, '验证文件保存失败');
        }
        E(0, '验证文件保存成功');
    }

    /**
     * upload 是 save 的路由别名，兼容后台菜单使用 upload 命名的配置。
     */
    public function upload()
    {
        return $this->save();
    }

    /**
     * 为搜索引擎公开读取验证文件。
     *
     * 该方法不要求后台登录；模型仍执行 engine、filename 与 site_id 隔离，
     * 因而只会暴露已注册的公开验证内容。
     *
     * @return string|false
     */
    public function serve($site_id, $engine, $filename)
    {
        $model = new seo_verification((int)$site_id, $this->db);
        $content = $model->get((int)$site_id, (string)$engine, (string)$filename);
        if ($content === false) {
            if (function_exists('http_response_code')) {
                http_response_code(404);
            }
            return false;
        }

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo $content;
        return $content;
    }
}

?>
