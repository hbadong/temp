<?php
/**
 * 语言配置模型
 * 表: le_language_config
 */
class language_config extends model {

    public function __construct() {
        $this->table = 'language_config';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取站点启用的语言列表（含全局默认）
     * @param int $site_id 站点ID（0=全局）
     * @return array 按 sort 排序的语言配置列表
     */
    public function get_enabled($site_id = 0) {
        $site_id = (int)$site_id;
        $where = array('is_enabled' => 1);
        $list = $this->find_fetch($where, array('sort' => 1));

        // 过滤站点作用域（site_id = 当前站点 或 全局）
        $result = array();
        foreach ($list as $row) {
            if ((int)$row['site_id'] === $site_id || (int)$row['site_id'] === 0) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * 获取站点默认语言代码
     * @param int $site_id 站点ID
     * @return string 语言代码（如 'zh'）
     */
    public function get_default($site_id = 0) {
        $list = $this->get_enabled($site_id);
        foreach ($list as $lang) {
            if ($lang['is_default'] == 1) {
                return $lang['language'];
            }
        }
        // 回退到第一个启用的语言
        return !empty($list) ? $list[0]['language'] : 'zh';
    }

    /**
     * 获取语言信息（按 site_id + language 精确匹配，站点优先）
     * @param int $site_id 站点ID
     * @param string $language 语言代码
     * @return array|false
     */
    public function get_lang($site_id, $language) {
        $site_id = (int)$site_id;
        $language = addslashes($language);

        // 优先查站点级配置
        $row = $this->find_fetch(array('site_id' => $site_id, 'language' => $language), array(), 0, 1);

        // 站点级不存在则查全局
        if (empty($row)) {
            $row = $this->find_fetch(array('site_id' => 0, 'language' => $language), array(), 0, 1);
        }

        return !empty($row) ? $row[0] : false;
    }

    /**
     * 批量写入/更新语言配置（站点级覆盖全局）
     * @param int $site_id 站点ID
     * @param array $configs 语言配置数组 [{language, language_name, is_default, is_enabled, subdomain_enabled, prompt_template, sort}, ...]
     * @return bool
     */
    public function save_site_config($site_id, $configs) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;

        // 先删除该站点的所有语言配置
        $this->db->query("DELETE FROM `{$tablepre}language_config` WHERE site_id = " . $site_id);

        // 批量插入
        foreach ($configs as $cfg) {
            $this->create(array(
                'site_id' => $site_id,
                'language' => $cfg['language'],
                'language_name' => $cfg['language_name'],
                'is_default' => isset($cfg['is_default']) ? (int)$cfg['is_default'] : 0,
                'is_enabled' => isset($cfg['is_enabled']) ? (int)$cfg['is_enabled'] : 1,
                'subdomain_enabled' => isset($cfg['subdomain_enabled']) ? (int)$cfg['subdomain_enabled'] : 0,
                'prompt_template' => isset($cfg['prompt_template']) ? $cfg['prompt_template'] : '',
                'sort' => isset($cfg['sort']) ? (int)$cfg['sort'] : 0,
            ));
        }

        return true;
    }
}
