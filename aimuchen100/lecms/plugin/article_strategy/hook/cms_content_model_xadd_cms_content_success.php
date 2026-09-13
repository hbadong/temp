<?php
/**
 * 文章创建时自动应用标题规则 Hook
 * 在文章创建成功后，根据站点激活的标题规则自动生成文章标题
 *
 * Hook 点位置：lecms/model/cms_content_model.class.php 的 xadd() 方法中
 *             $id 赋值成功后（cms_content_model_xadd_cms_content_success）
 *
 * 注意：本文件会被内联进 xadd() 方法体，禁止使用 return 中断文章创建流程
 */

// 加载标题规则引擎（文件名不符合 core::model() 约定，显式 require）
require_once ROOT_PATH . 'lecms/plugin/article_strategy/model/title_rule_engine.php';

// 获取站点 ID
$site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);

if (!empty($site_id)) {
    // 获取分类 ID（从 $_POST 或 $_GET 获取）
    $cid = 0;
    if (isset($_POST['cid'])) {
        $cid = (int)$_POST['cid'];
    } elseif (isset($_GET['cid'])) {
        $cid = (int)$_GET['cid'];
    }

    // 加载标题规则模型
    $title_rule_model = core::model('title_rule');

    // 查询该分类下的激活标题规则
    $tablepre = $_ENV['_config']['db']['master']['tablepre'];
    $rules = $title_rule_model->get_active($site_id);

    // 过滤出有模板的规则
    $valid_rules = array();
    if (!empty($rules) && is_array($rules)) {
        foreach ($rules as $rule) {
            $templates = json_decode($rule['templates'], true);
            if (!empty($templates) && is_array($templates)) {
                $valid_rules[] = $rule;
            }
        }
    }

    if (!empty($valid_rules)) {
        // 随机选择一个规则
        $selected_rule = $valid_rules[array_rand($valid_rules)];
        $templates = json_decode($selected_rule['templates'], true);
        $template = $templates[array_rand($templates)];

        // 解析正则替换规则
        $regex_rules = array();
        $regex_json = $selected_rule['regex_rules'];
        if ($regex_json && $regex_json !== '[]') {
            $regex_rules = json_decode($regex_json, true);
            if (!is_array($regex_rules)) {
                $regex_rules = array();
            }
        }

        // 收集模板变量
        $vars = array();

        // site_name：从站点管理获取
        $site_model = core::model('site_manager');
        $site = $site_model->get($site_id);
        $vars['site_name'] = !empty($site['name']) ? $site['name'] : '';

        // category：从分类获取
        $category_model = core::model('category');
        $category = $category_model->get($cid);
        $vars['category'] = !empty($category['name']) ? $category['name'] : '';

        // game_name: through category lookup game
        $game = $this->db->fetch_first("SELECT name FROM `{$tablepre}cms_game` WHERE site_id = {$site_id} AND cid = {$cid} LIMIT 1");
        $vars['game_name'] = !empty($game['name']) ? $game['name'] : '';

        // date / year / month
        $vars['date'] = date('Ymd');
        $vars['year'] = date('Y');
        $vars['month'] = date('m');

        // 渲染标题
        $engine = new title_rule_engine();
        $generated_title = $engine->render($template, $vars, $regex_rules);

        if (!empty($generated_title) && isset($id) && $id > 0) {
            // 文章已创建，回写数据库标题
            $title = addslashes($generated_title);
            $id = (int)$id;
            $this->db->query("UPDATE `{$tablepre}cms_article` SET title = '{$title}' WHERE id = {$id} LIMIT 1");
        }
    }
}
