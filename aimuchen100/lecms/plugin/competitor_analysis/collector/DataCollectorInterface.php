<?php
/**
 * 数据采集器接口
 */
interface DataCollectorInterface {
    /**
     * 采集数据
     * @param string $url 目标URL
     * @return array 采集结果
     */
    public function collect($url);

    /**
     * 获取数据类型标识
     * @return string
     */
    public function getDataType();
}
