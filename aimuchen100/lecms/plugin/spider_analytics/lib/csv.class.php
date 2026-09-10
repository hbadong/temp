<?php
/**
 * CSV 解析与导出
 */
class spider_csv {
    const HEADER = 'site_id,match_type,match_value,note,enabled';

    public static function parse($content) {
        if (!is_string($content)) throw new Exception('content not string');
        $content = trim($content);
        if ($content === '') throw new Exception('empty csv');
        $lines = preg_split("/\r?\n/", $content);
        $header = str_getcsv(array_shift($lines));
        $expected = explode(',', self::HEADER);
        if ($header !== $expected) {
            throw new Exception('invalid header: ' . implode(',', $header));
        }
        $rows = array();
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $cols = str_getcsv($line);
            $padded = array_slice(array_pad($cols, count($expected), ''), 0, count($expected));
            $rows[] = array_combine($expected, $padded);
        }
        return $rows;
    }

    public static function emit($rows) {
        $out = self::HEADER . "\n";
        foreach ($rows as $r) {
            $out .= implode(',', array_map(array('spider_csv', 'quote'), $r)) . "\n";
        }
        return $out;
    }

    public static function quote($v) {
        $v = (string)$v;
        if (strpos($v, ',') !== false || strpos($v, '"') !== false || strpos($v, "\n") !== false) {
            return '"' . str_replace('"', '""', $v) . '"';
        }
        return $v;
    }
}
