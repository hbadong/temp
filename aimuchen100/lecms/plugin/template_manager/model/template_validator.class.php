<?php
defined('ROOT_PATH') || exit;

/**
 * TemplateValidator 模型 — 标签闭合与语法检查
 */
class TemplateValidator
{
    /**
     * 检查模板语法
     * @return array 错误列表 [{line, column, code, message}]
     */
    public function check($content)
    {
        $errors = [];
        $lines = explode("\n", $content);

        // 检查未闭合标签
        $stack = [];
        $tags = ['if', 'loop', 'block', 'inc'];

        foreach ($lines as $line_num => $line) {
            // 检查 LECMS 标签
            if (preg_match_all('/\{(' . implode('|', $tags) . '):/', $line, $matches)) {
                foreach ($matches[1] as $tag) {
                    $stack[] = ['tag' => $tag, 'line' => $line_num + 1];
                }
            }

            // 检查闭合标签
            if (preg_match_all('/\{\/(if|loop|block|inc)\}/', $line, $matches)) {
                array_pop($stack);
            }
        }

        if (!empty($stack)) {
            foreach ($stack as $unclosed) {
                $errors[] = [
                    'line' => $unclosed['line'],
                    'column' => 1,
                    'code' => 'UNCLOSED_TAG',
                    'message' => "未闭合的 {{$unclosed['tag']}} 标签"
                ];
            }
        }

        return $errors;
    }
}
