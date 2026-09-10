<?php
/**
 * TXT/CSV 文章导入解析器
 * 支持纯文本(.txt)和CSV(.csv)两种格式
 * 使用md5标题哈希去重，每批处理1000篇
 */
class txt_importer {

    const BATCH_SIZE = 1000;

    /**
     * 检测文件格式
     * @param string $filename 文件名
     * @return string 'txt'|'csv'
     */
    public function detect_format($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return 'csv';
        }
        return 'txt';
    }

    /**
     * 解析上传的文件，返回文章数组
     * @param string $file_path 上传文件的临时路径
     * @param string $filename 原始文件名
     * @return array 解析结果 ['articles' => [], 'total' => int, 'format' => string]
     */
    public function parse($file_path, $filename) {
        $format = $this->detect_format($filename);

        switch ($format) {
            case 'csv':
                return $this->parse_csv($file_path);
            case 'txt':
            default:
                return $this->parse_txt($file_path);
        }
    }

    /**
     * 解析纯文本文件
     * 按空行分段，每段第一行为标题，剩余为正文
     * @param string $file_path 文件路径
     * @return array 解析结果
     */
    protected function parse_txt($file_path) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return array('articles' => array(), 'total' => 0, 'format' => 'txt');
        }

        // 统一换行符
        $content = str_replace(array("\r\n", "\r"), "\n", $content);

        // 按空行分段（连续两个换行符）
        $blocks = preg_split('/\n{2,}/', $content);

        $articles = array();
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            // 第一行为标题，剩余为正文
            $pos = strpos($block, "\n");
            if ($pos !== false) {
                $title = trim(substr($block, 0, $pos));
                $body = trim(substr($block, $pos + 1));
            } else {
                $title = trim($block);
                $body = '';
            }

            if ($title === '') {
                continue;
            }

            $articles[] = array(
                'title' => $title,
                'content' => $body,
                'tags' => '',
                'category' => '',
                'hash' => md5($title),
            );
        }

        return array(
            'articles' => $articles,
            'total' => count($articles),
            'format' => 'txt',
        );
    }

    /**
     * 解析CSV文件
     * 检测列：title, content, tags, category
     * @param string $file_path 文件路径
     * @return array 解析结果
     */
    protected function parse_csv($file_path) {
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return array('articles' => array(), 'total' => 0, 'format' => 'csv');
        }

        // 读取表头
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return array('articles' => array(), 'total' => 0, 'format' => 'csv');
        }

        // 标准化表头（小写，去BOM）
        $header = array_map(function($col) {
            $col = trim($col);
            // 去除BOM
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return strtolower($col);
        }, $header);

        // 映射列索引
        $col_map = array();
        $required_cols = array('title', 'content', 'tags', 'category');
        foreach ($header as $index => $col) {
            if (in_array($col, $required_cols)) {
                $col_map[$col] = $index;
            }
        }

        // 至少要有title列
        if (!isset($col_map['title'])) {
            fclose($handle);
            return array('articles' => array(), 'total' => 0, 'format' => 'csv', 'error' => 'Missing required column: title');
        }

        $articles = array();
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            }

            $title = isset($row[$col_map['title']]) ? trim($row[$col_map['title']]) : '';
            if ($title === '') {
                continue;
            }

            $articles[] = array(
                'title' => $title,
                'content' => isset($col_map['content']) ? trim($row[$col_map['content']]) : '',
                'tags' => isset($col_map['tags']) ? trim($row[$col_map['tags']]) : '',
                'category' => isset($col_map['category']) ? trim($row[$col_map['category']]) : '',
                'hash' => md5($title),
            );
        }

        fclose($handle);

        return array(
            'articles' => $articles,
            'total' => count($articles),
            'format' => 'csv',
        );
    }

    /**
     * 分批获取文章（每批1000篇）
     * @param array $articles 全部文章数组
     * @return array 分批后的数组
     */
    public function batch($articles) {
        $batches = array();
        $total = count($articles);
        for ($i = 0; $i < $total; $i += self::BATCH_SIZE) {
            $batches[] = array_slice($articles, $i, self::BATCH_SIZE);
        }
        return $batches;
    }

    /**
     * 获取每批大小
     * @return int
     */
    public function get_batch_size() {
        return self::BATCH_SIZE;
    }
}
