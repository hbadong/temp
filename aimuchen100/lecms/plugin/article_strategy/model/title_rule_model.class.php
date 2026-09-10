<?php
/**
 * 标题规则模型
 * 管理文章标题规则（模板 + 正则替换）
 */
class title_rule extends model {

    public function __construct() {
        $this->table = 'article_title_rule';
        $this->pri = array('sid');
        $this->maxid = 'sid';
    }

    /**
     * 获取站点下所有标题规则
     * @param int $site_id 站点ID
     * @return array 规则列表
     */
    public function get_list($site_id) {
        $where = array('site_id' => $site_id);
        return $this->find_fetch($where, array('sid' => 1));
    }

    /**
     * 获取站点下已激活的标题规则
     * @param int $site_id 站点ID
     * @return array 激活的规则列表
     */
    public function get_active($site_id) {
        $where = array('site_id' => $site_id, 'is_active' => 1);
        return $this->find_fetch($where, array('sid' => 1));
    }

    /**
     * 创建标题规则
     * @param array $arr 规则数据
     * @return int|bool 新规则SID或false
     */
    public function create($arr) {
        $arr['site_id'] = isset($arr['site_id']) ? (int)$arr['site_id'] : 0;
        $arr['name'] = isset($arr['name']) ? trim($arr['name']) : '';
        $arr['templates'] = isset($arr['templates']) ? $arr['templates'] : '[]';
        $arr['regex_rules'] = isset($arr['regex_rules']) ? $arr['regex_rules'] : '[]';
        $arr['is_active'] = isset($arr['is_active']) ? (int)$arr['is_active'] : 1;
        $arr['created_at'] = $_ENV['_time']; // created_at/updated_at 列为 INT UNSIGNED
        $arr['updated_at'] = $_ENV['_time'];

        return parent::create($arr);
    }

    /**
     * 更新标题规则（与父类 model::update($data, $life) 签名冲突，另命名为 save）
     * @param int $sid 规则SID
     * @param array $arr 更新数据
     * @return bool
     */
    public function save($sid, $arr) {
        if (isset($arr['site_id'])) {
            $arr['site_id'] = (int)$arr['site_id'];
        }
        if (isset($arr['name'])) {
            $arr['name'] = trim($arr['name']);
        }
        if (isset($arr['templates'])) {
            $arr['templates'] = $arr['templates'];
        }
        if (isset($arr['regex_rules'])) {
            $arr['regex_rules'] = $arr['regex_rules'];
        }
        if (isset($arr['is_active'])) {
            $arr['is_active'] = (int)$arr['is_active'];
        }
        $arr['updated_at'] = $_ENV['_time'];
        $arr['sid'] = $sid;

        return parent::update($arr);
    }
}
