<?php
/**
 * SeoResolver - 三级 SEO 继承解析器（REQ-07-AC2/AC3/AC4）
 *
 * 优先级：内容级（cms_article.seo_title 等） → 分类级（category.seo_title 等） → 全局默认（runtime KV seo_default_config）
 *
 * 设计要点：
 * - 纯静态方法，可独立测试：接收 $db（LECMS db 接口）、$tablepre、$runtime（xget 方法）、$get（$_GET 数组）、$fallback_cfg（站点配置，含 webname）。
 * - 由 hook/base_control_construct_after.php 在 base_control 构造末尾调用，$this->db / $this->runtime / $this->_cfg 传入。
 * - SQL 值使用 addslashes()/intval() 内联（LECMS 无参数绑定），表名用 $tablepre 拼接。
 * - 无触发点的旧 hook/cms_template_render.php 已废弃（核心源码无 `// hook cms_template_render.php`
 *   标记、主题模板无 `{hook:cms_template_render.php}` 引用），本类承载三级继承逻辑。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */
class seo_resolver
{
    /**
     * 解析页面 SEO 元数据（三级继承）。
     *
     * @param object      $db           LECMS 数据库对象（query/fetch_first/fetch_all + tablepre）
     * @param string      $tablepre     数据表前缀
     * @param object|null $runtime      运行时 KV 对象（提供 xget($key)），用于读取 seo_default_config
     * @param array       $get          $_GET 数组（id / alias）
     * @param array       $fallback_cfg 站点配置（含 webname），全局默认 title 的最终兜底
     * @return array  ['title' => string, 'keywords' => string, 'description' => string]
     */
    public static function resolve($db, $tablepre, $runtime, $get, $fallback_cfg = array())
    {
        $seo = array('title' => '', 'keywords' => '', 'description' => '');
        $content_cid = null;
        $get = is_array($get) ? $get : array();

        // 1. 内容级 SEO（最高优先级）
        if (isset($get['id']) || isset($get['alias'])) {
            $content_id = isset($get['id']) ? (int)$get['id'] : 0;
            if (!$content_id && isset($get['alias']) && $get['alias'] !== '') {
                // 通过别名查找内容 ID（pre_only_alias 表可能不存在，静默处理）
                try {
                    $alias = addslashes((string)$get['alias']);
                    $alias_row = $db->fetch_first("
                        SELECT id FROM `{$tablepre}only_alias`
                        WHERE alias = '{$alias}' LIMIT 1
                    ");
                } catch (Exception $e) {
                    $alias_row = false;
                }
                $content_id = ($alias_row && isset($alias_row['id'])) ? (int)$alias_row['id'] : 0;
            }

            if ($content_id) {
                try {
                    $content = $db->fetch_first("
                        SELECT subject, seo_title, seo_keywords, seo_description, cid
                        FROM `{$tablepre}cms_article`
                        WHERE id = " . (int)$content_id . " AND status = 1
                        LIMIT 1
                    ");
                } catch (Exception $e) {
                    $content = false;
                }

                if ($content) {
                    // 三级继承语义：seo_title 未设置（空）时不使用 subject 兜底，
                    // 保持 title 为空以进入分类级回退（subject 仅是内容标题，不参与 SEO 继承链）。
                    $seo['title'] = !empty($content['seo_title']) ? $content['seo_title'] : '';
                    $seo['keywords'] = isset($content['seo_keywords']) ? $content['seo_keywords'] : '';
                    $seo['description'] = isset($content['seo_description']) ? $content['seo_description'] : '';
                    $content_cid = isset($content['cid']) ? $content['cid'] : null;
                }
            }
        }

        // 2. 分类级 SEO（中间优先级，仅内容级 title 为空时回退）
        if ($content_cid !== null && empty($seo['title'])) {
            try {
                $category = $db->fetch_first("
                    SELECT name, seo_title, seo_keywords, seo_description
                    FROM `{$tablepre}category`
                    WHERE cid = " . (int)$content_cid . "
                    LIMIT 1
                ");
            } catch (Exception $e) {
                $category = false;
            }

            if ($category) {
                $seo['title'] = !empty($category['seo_title']) ? $category['seo_title'] : $category['name'];
                $seo['keywords'] = isset($category['seo_keywords']) ? $category['seo_keywords'] : '';
                $seo['description'] = isset($category['seo_description']) ? $category['seo_description'] : '';
            }
        }

        // 3. 全局默认 SEO（最低优先级）
        if (empty($seo['title'])) {
            $default = null;
            if ($runtime !== null && method_exists($runtime, 'xget')) {
                try {
                    $default = $runtime->xget('seo_default_config');
                } catch (Exception $e) {
                    $default = null;
                }
            }
            $default = is_array($default) ? $default : array();
            $fallback_cfg = is_array($fallback_cfg) ? $fallback_cfg : array();

            $seo['title'] = !empty($default['title']) ? $default['title']
                : (isset($fallback_cfg['webname']) ? $fallback_cfg['webname'] : '');
            $seo['keywords'] = !empty($default['keywords']) ? $default['keywords'] : '';
            $seo['description'] = !empty($default['description']) ? $default['description'] : '';
        }

        return $seo;
    }
}
