<?php
defined('ROOT_PATH') or exit;

/**
 * 企业站点生成向导控制器
 */

class generate_control extends admin_control {

    /**
     * 生成向导表单
     */
    public function index() {
        $this->display('generate_wizard.htm');
    }

    /**
     * 执行生成
     */
    public function create_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        // 接收表单数据
        $enterprise_name = trim(R('enterprise_name', 'P'));
        $industry = trim(R('industry', 'P'));
        $address = trim(R('address', 'P'));
        $phone = trim(R('phone', 'P'));
        $email = trim(R('email', 'P'));
        $description = trim(R('description', 'P'));
        $template_id = (int)R('template_id', 'P');

        if (empty($enterprise_name)) {
            E(1, '企业名称不能为空');
        }

        // 创建企业站点记录
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $data = array(
            'site_id' => $site_id,
            'enterprise_name' => $enterprise_name,
            'industry' => $industry,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'description' => $description,
            'template_id' => $template_id,
            'status' => 1, // 已发布
        );

        $id = $this->db->insert("`{$tablepre}enterprise_site`", $data);

        if ($id) {
            // 触发 AI 内容生成（异步）
            $this->generate_content($id, $data);
            E(0, '企业站点创建成功');
        } else {
            E(1, '创建失败，请重试');
        }
    }

    /**
     * AI 生成企业内容
     */
    private function generate_content($enterprise_id, $enterprise_data) {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        // 调用 AI 内容生成器（Task 1.5 实现）
        // 此处为简化实现，实际在 Task 1.5 中完善
        $generator = new enterprise_content_generator($site_id, $this->db);
        $generator->generate_for_enterprise($enterprise_id, $enterprise_data);
    }
}
