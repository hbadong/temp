<?php
/**
 * AI搜索适配器接口
 */
interface AiSearchAdapterInterface {
    /**
     * 执行搜索监测
     * @param string $keyword 品牌关键词
     * @return array 监测结果
     */
    public function search($keyword);

    /**
     * 获取平台名称
     * @return string
     */
    public function getPlatformName();
}
