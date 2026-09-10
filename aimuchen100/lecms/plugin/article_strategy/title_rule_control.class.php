<?php
defined('ROOT_PATH') or exit;


class title_rule_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        $cols = "{field: 'sid', width: 80, title: 'ID', align: 'center'},";
        $cols .= "{field: 'name', minWidth: 150, title: '规则名称'},";
        $cols .= "{field: 'template_preview', minWidth: 200, title: '模板预览'},";
        $cols .= "{field: 'is_active', width: 100, title: '状态', align: 'center', templet: '#status-tpl'},";
        $cols .= "{field: 'created_at', width: 170, title: '创建时间', align: 'center'},";
        $cols .= "{title: '操作', width: 180, toolbar: '#op-bar', align: 'center'}";
        $this->assign('cols', $cols);
        $this->display('title_rule_list.htm');
    }

    public function get_list() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $rules = $this->title_rule->get_list($site_id);

        $data_arr = array();
        foreach ($rules as $rule) {
            $templates = json_decode($rule['templates'], true);
            $preview = '';
            if (!empty($templates) && is_array($templates) && isset($templates[0])) {
                $preview = $templates[0];
                if (mb_strlen($preview) > 50) {
                    $preview = mb_substr($preview, 0, 50) . '...';
                }
            }

            $regex_count = 0;
            $regex_rules = json_decode($rule['regex_rules'], true);
            if (!empty($regex_rules) && is_array($regex_rules)) {
                $regex_count = count($regex_rules);
            }

            $data_arr[] = array(
                'sid' => $rule['sid'],
                'name' => $rule['name'],
                'template_preview' => $preview . ($regex_count > 0 ? ' <span class="layui-badge layui-bg-gray">' . $regex_count . ' 条规则</span>' : ''),
                'is_active' => $rule['is_active'],
                'created_at' => $rule['created_at'],
            );
        }

        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => count($data_arr),
            'data' => $data_arr,
        );
        exit(json_encode($arr));
    }

    public function create() {
        // assign() 通过引用接收参数，临时数组/字面量会触发 "Cannot pass parameter 2 by reference"，需先赋给变量
        $empty_data = array();
        $create_action = 'create';
        $this->assign('data', $empty_data);
        $this->assign('action', $create_action);
        $this->display('title_rule_create.htm');
    }

    public function create_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $name = trim(R('name', 'P'));
        if (empty($name)) {
            $this->message(1, '规则名称不能为空');
        }

        $templates_raw = trim(R('templates', 'P'));
        $templates = $this->parse_templates($templates_raw);
        if (empty($templates)) {
            $this->message(1, '至少需要一个标题模板');
        }

        $regex_rules_raw = trim(R('regex_rules', 'P'));
        $regex_rules = array();
        if ($regex_rules_raw) {
            $decoded = json_decode($regex_rules_raw, true);
            if (is_array($decoded)) {
                $regex_rules = $decoded;
            }
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $is_active = R('is_active', 'P') ? 1 : 0;

        $data = array(
            'site_id' => $site_id,
            'name' => $name,
            'templates' => _json_encode($templates),
            'regex_rules' => _json_encode($regex_rules),
            'is_active' => $is_active,
        );

        $sid = $this->title_rule->create($data);
        if ($sid) {
            $this->message(0, '创建成功', 'index.php?title_rule-index');
        } else {
            $this->message(1, '创建失败');
        }
    }

    public function edit() {
        $sid = (int)R('sid', 'R');
        if (empty($sid)) {
            $this->message(1, lang('data_error'), 'index.php?title_rule-index');
        }

        $rule = $this->title_rule->get($sid);
        if (empty($rule)) {
            $this->message(1, lang('data_no_exists'), 'index.php?title_rule-index');
        }

        $templates = json_decode($rule['templates'], true);
        if (is_array($templates)) {
            $rule['templates_text'] = implode("
", $templates);
        } else {
            $rule['templates_text'] = '';
        }

        $regex_rules = json_decode($rule['regex_rules'], true);
        $rule['regex_rules_json'] = is_array($regex_rules) ? _json_encode($regex_rules) : '[]';

        $this->assign('data', $rule);
        // assign() 通过引用接收参数，字面量会触发 "Cannot pass parameter 2 by reference"，需先赋给变量
        $edit_action = 'edit';
        $this->assign('action', $edit_action);
        $this->display('title_rule_create.htm');
    }

    public function edit_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $sid = (int)R('sid', 'P');
        if (empty($sid)) {
            $this->message(1, lang('data_error'), 'index.php?title_rule-index');
        }

        $rule = $this->title_rule->get($sid);
        if (empty($rule)) {
            $this->message(1, lang('data_no_exists'), 'index.php?title_rule-index');
        }

        $name = trim(R('name', 'P'));
        if (empty($name)) {
            $this->message(1, '规则名称不能为空');
        }

        $templates_raw = trim(R('templates', 'P'));
        $templates = $this->parse_templates($templates_raw);
        if (empty($templates)) {
            $this->message(1, '至少需要一个标题模板');
        }

        $regex_rules_raw = trim(R('regex_rules', 'P'));
        $regex_rules = array();
        if ($regex_rules_raw) {
            $decoded = json_decode($regex_rules_raw, true);
            if (is_array($decoded)) {
                $regex_rules = $decoded;
            }
        }

        $is_active = R('is_active', 'P') ? 1 : 0;

        $data = array(
            'name' => $name,
            'templates' => _json_encode($templates),
            'regex_rules' => _json_encode($regex_rules),
            'is_active' => $is_active,
        );

        if ($this->title_rule->save($sid, $data)) {
            $this->message(0, '更新成功', 'index.php?title_rule-index');
        } else {
            $this->message(1, '更新失败');
        }
    }

    public function delete() {
        $sid = (int)R('sid', 'P');
        if (empty($sid)) {
            $this->message(1, lang('data_error'));
        }

        $rule = $this->title_rule->get($sid);
        if (empty($rule)) {
            $this->message(1, lang('data_no_exists'));
        }

        if ($this->title_rule->delete($sid)) {
            $this->message(0, lang('delete_successfully'));
        } else {
            $this->message(1, lang('delete_failed'));
        }
    }

    public function toggle() {
        $sid = (int)R('sid', 'P');
        if (empty($sid)) {
            $this->message(1, lang('data_error'));
        }

        $rule = $this->title_rule->get($sid);
        if (empty($rule)) {
            $this->message(1, lang('data_no_exists'));
        }

        $new_status = $rule['is_active'] == 1 ? 0 : 1;
        if ($this->title_rule->save($sid, array('is_active' => $new_status))) {
            $status_text = $new_status ? '已启用' : '已禁用';
            $this->message(0, $status_text);
        } else {
            $this->message(1, '操作失败');
        }
    }

    /**
     * Parse template text into array (shared by create_post / edit_post)
     */
    private function parse_templates($raw) {
        $templates = array();
        if ($raw) {
            $lines = explode("\n", str_replace("\r\n", "\n", $raw));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $templates[] = $line;
                }
            }
        }
        return $templates;
    }

    /**
     * 插件设置页面（已合并进 category_import-index 第二个 tab）
     * 兼容重定向：旧入口跳转到主页面设置 tab
     */
    public function settings() {
        $this->message(0, '', 'index.php?category_import-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $interval = (int)R('collect_interval', 'P');
        if ($interval < 1) {
            $interval = 1;
        }
        if ($interval > 1440) {
            $interval = 1440;
        }

        $random = (int)R('random_factor', 'P');
        if ($random < 0) {
            $random = 0;
        }
        if ($random > 100) {
            $random = 100;
        }

        $concurrent = (int)R('max_concurrent', 'P');
        if ($concurrent < 1) {
            $concurrent = 1;
        }
        if ($concurrent > 20) {
            $concurrent = 20;
        }

        $settings = array(
            'default_title_rule' => trim(R('default_title_rule', 'P')),
            'collect_interval' => $interval,
            'random_factor' => $random,
            'max_concurrent' => $concurrent,
        );

        $this->runtime->set('article_strategy_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }
}
