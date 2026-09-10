<?php
defined('ROOT_PATH') || exit;

class spider_dbdump {
    public static $pdo;
    public static $pre = 'le_';
    public static $batch = 1000;
    public static $tables = array(
        'user', 'category', 'content', 'url', 'tag', 'comment',
        'plugin_data', 'navigate', 'block',
    );
    public static function init($pdo, $pre = 'le_') { self::$pdo = $pdo; self::$pre = $pre; }
    public function export_to_string($site_id) {
        $out = "-- data-export SQL dump for site_id={$site_id}\n-- generated: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach (self::$tables as $t) {
            $real = self::$pre . $t;
            if (!self::table_exists($real)) continue;
            $out .= "-- table: {$real}\n";
            $has_site = self::table_has_column($real, 'site_id');
            $where = $has_site ? "WHERE site_id=" . (int)$site_id : '';
            $out .= "DELETE FROM `{$real}` {$where};\n";
            $offset = 0;
            while (true) {
                $sql = "SELECT * FROM `{$real}` {$where} LIMIT " . self::$batch . " OFFSET {$offset}";
                $rows = self::$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (empty($rows)) break;
                $cols = array_keys($rows[0]);
                $col_list = '`' . implode('`,`', $cols) . '`';
                foreach ($rows as $r) {
                    $vals = array_map(function($v) {
                        if ($v === null) return 'NULL';
                        return "'" . str_replace("'", "\\'", (string)$v) . "'";
                    }, array_values($r));
                    $out .= "INSERT INTO `{$real}` ({$col_list}) VALUES (" . implode(',', $vals) . ");\n";
                }
                $offset += self::$batch;
                if (count($rows) < self::$batch) break;
            }
            $out .= "\n";
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $out;
    }
    private static function table_exists($name) {
        try {
            $driver = self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $r = self::$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$name}'")->fetch();
            } else {
                $r = self::$pdo->query("SHOW TABLES LIKE '{$name}'")->fetch();
            }
            return $r !== false;
        } catch (Exception $e) { return false; }
    }
    private static function table_has_column($table, $column) {
        try {
            $driver = self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                // SQLite 下 getColumnMeta 对 LIMIT 0 结果集不可靠，改用 PRAGMA table_info
                $stmt = self::$pdo->query("PRAGMA table_info(`{$table}`)");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($r['name']) && strcasecmp($r['name'], $column) === 0) return true;
                }
                return false;
            }
            $stmt = self::$pdo->query("SELECT * FROM `{$table}` LIMIT 0");
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                if (isset($meta['name']) && strcasecmp($meta['name'], $column) === 0) return true;
            }
        } catch (Exception $e) {}
        return false;
    }
}
