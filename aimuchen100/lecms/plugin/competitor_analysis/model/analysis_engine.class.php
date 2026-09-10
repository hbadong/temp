<?php
/**
 * 竞品数据分析引擎
 * 调度采集器、聚合数据、调用 AI 生成建议
 */
class analysis_engine {

    private $site_id;
    private $db;
    private $collectors;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->collectors = $this->init_collectors();
    }

    /**
     * 初始化采集器
     */
    private function init_collectors() {
        return array(
            new domain_collector(),
            new title_collector(),
            new template_collector(),
            new server_collector(),
        );
    }

    /**
     * 分析单个竞品站点
     * @param array $competitor 竞品站点数据
     * @return array 分析结果
     */
    public function analyze_competitor($competitor) {
        $url = $competitor['url'];
        $results = array();

        foreach ($this->collectors as $collector) {
            try {
                $data = $collector->collect($url);
                $results[$collector->getDataType()] = $data;
            } catch (Exception $e) {
                $results[$collector->getDataType()] = array('error' => $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 生成分析报告
     * @param int $competitor_id 竞品站点ID
     * @param string $report_type 报告类型
     * @return int 报告ID
     */
    public function generate_report($competitor_id, $report_type = 'manual') {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $model = core::model('competitor_site');
        $competitor = $model->get_one($competitor_id);

        if (!$competitor) {
            return 0;
        }

        // 执行分析
        $analysis_data = $this->analyze_competitor($competitor);

        // 生成 AI 建议
        $suggestion_generator = new suggestion_generator($this->site_id, $this->db);
        $ai_suggestions = $suggestion_generator->generate($competitor, $analysis_data);

        // 存储报告
        $now = $_ENV['_time'];
        $report_id = $this->db->insert("`{$tablepre}competitor_report`", array(
            'site_id' => $this->site_id,
            'competitor_id' => $competitor_id,
            'report_type' => $report_type,
            'domain_authority' => isset($analysis_data['domain_authority']['authority_score'])
                ? (int)$analysis_data['domain_authority']['authority_score'] : 0,
            'title_data' => json_encode(isset($analysis_data['title_strategy']) ? $analysis_data['title_strategy'] : array()),
            'template_data' => json_encode(isset($analysis_data['template_structure']) ? $analysis_data['template_structure'] : array()),
            'server_info' => json_encode(isset($analysis_data['server_info']) ? $analysis_data['server_info'] : array()),
            'ai_suggestions' => json_encode($ai_suggestions),
            'analyzed_at' => $now,
            'created_at' => $now,
        ));

        // 更新最后分析时间
        $model->update_analyzed_time($competitor_id);

        return $report_id;
    }

    /**
     * 批量分析所有待分析站点
     * @return array 生成的报告ID列表
     */
    public function batch_analyze() {
        $model = core::model('competitor_site');
        $pending = $model->get_pending_analysis($this->site_id);
        $report_ids = array();

        foreach ($pending as $competitor) {
            $report_id = $this->generate_report($competitor['id'], 'weekly');
            if ($report_id) {
                $report_ids[] = $report_id;
            }
        }

        return $report_ids;
    }
}
