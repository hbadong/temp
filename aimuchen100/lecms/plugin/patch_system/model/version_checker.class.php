<?php
/**
 * 版本检查器
 * 负责语义化版本号解析与比较
 */
class version_checker {

    private $site_id;

    public function __construct($site_id = 0) {
        $this->site_id = (int)$site_id;
    }

    /**
     * 解析语义化版本号为可比较整数
     * @param string $version 如 "1.2.3"
     * @return int 如 100020003
     */
    public function parse($version) {
        $parts = explode('.', $version);
        $major = isset($parts[0]) ? (int)$parts[0] : 0;
        $minor = isset($parts[1]) ? (int)$parts[1] : 0;
        $patch = isset($parts[2]) ? (int)$parts[2] : 0;
        return $major * 100000000 + $minor * 1000 + $patch;
    }

    /**
     * 比较版本号
     * @param string $v1
     * @param string $v2
     * @return int -1 / 0 / 1
     */
    public function compare($v1, $v2) {
        return version_compare($v1, $v2);
    }

    /**
     * 检查版本是否在范围内
     * @param string $version 当前版本
     * @param string $min 最低版本
     * @param string $max 最高版本
     * @return bool
     */
    public function is_in_range($version, $min = '0.0.0', $max = '999.999.999') {
        return $this->compare($version, $min) >= 0 && $this->compare($version, $max) <= 0;
    }
}
