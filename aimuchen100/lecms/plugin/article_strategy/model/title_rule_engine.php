<?php
/**
 * 标题规则引擎
 * 纯 PHP 模板解析类，零依赖
 * 支持变量替换和正则替换
 */
class title_rule_engine {

    /**
     * 渲染标题模板
     * @param string $template 标题模板，支持 {{var}} 变量
     * @param array $vars 模板变量数组
     * @param array $regex_rules 正则替换规则数组，每项包含 pattern 和 replacement
     * @return string 渲染后的标题
     */
    public function render($template, $vars = array(), $regex_rules = array()) {
        if (empty($template)) {
            return '';
        }

        // 第一步：变量替换
        $result = $this->replace_vars($template, $vars);

        // 第二步：正则替换
        if (!empty($regex_rules) && is_array($regex_rules)) {
            $result = $this->apply_regex_rules($result, $regex_rules);
        }

        return $result;
    }

    /**
     * 变量替换
     * @param string $template
     * @param array $vars
     * @return string
     */
    protected function replace_vars($template, $vars) {
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($vars) {
                $key = $matches[1];

                // 处理 random_N 格式（N=位数）
                if (strpos($key, 'random_') === 0) {
                    $digits = (int)substr($key, 7);
                    if ($digits > 0) {
                        $min = (int)str_repeat('1', $digits - 1) . '0';  // N=2 → 10
                        $max = (int)str_repeat('9', $digits);
                        return str_pad((string)mt_rand($min, $max), $digits, '0', STR_PAD_LEFT);
                    }
                    return $matches[0]; // 保留原样
                }

                // 从变量数组中获取值
                if (isset($vars[$key])) {
                    return $vars[$key];
                }

                // 未匹配变量保留原样
                return $matches[0];
            },
            $template
        );
    }

    /**
     * 应用正则替换规则
     * @param string $text
     * @param array $regex_rules
     * @return string
     */
    protected function apply_regex_rules($text, $regex_rules) {
        foreach ($regex_rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $pattern = isset($rule['pattern']) ? $rule['pattern'] : '';
            $replacement = isset($rule['replacement']) ? $rule['replacement'] : '';

            if ($pattern === '' || $pattern === null) {
                continue;
            }

            // 执行正则替换并处理错误
            $result = @preg_replace($pattern, $replacement, $text);
            if ($result === null) {
                continue; // 跳过无效规则
            }
            $text = $result;
        }

        return $text;
    }
}
