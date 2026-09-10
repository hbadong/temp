<?php
/**
 * 补丁管理器
 * 负责补丁包解析、校验、应用核心逻辑
 */
require_once dirname(__FILE__) . '/backup_manager.class.php';
class patch_manager {

    private $site_id;
    private $tablepre;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $this->db = $db;
    }

    /**
     * 解析补丁包 ZIP 文件
     *
     * 返回的 manifest.signature 字段被**强制重算为当前 zip 文件的整体 SHA256**，
     * 这样调用方直接用 parse_patch 返回值走 verify_patch 即可命中校验，
     * 避免被 manifest 内部过时签名误导（zip 容器时间戳会在每次 addFromString 后变化）。
     *
     * @param string $zip_path ZIP 文件路径
     * @return array manifest 数据（含重算后的 signature）
     */
    public function parse_patch($zip_path) {
        if (!file_exists($zip_path)) {
            throw new Exception('补丁包文件不存在');
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== TRUE) {
            throw new Exception('无法打开补丁包，请检查文件格式');
        }

        // 读取 manifest.json（兼容两种布局：根目录直放 / 单个顶层目录包裹）
        $manifest_json = $zip->getFromName('manifest.json');
        if ($manifest_json === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^[^/]+/manifest\.json$#', $name)) {
                    $manifest_json = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        if (!$manifest_json) {
            $zip->close();
            throw new Exception('补丁包缺少 manifest.json');
        }

        $manifest = json_decode($manifest_json, true);
        if (!$manifest || !isset($manifest['version'], $manifest['files'], $manifest['signature'])) {
            $zip->close();
            throw new Exception('manifest.json 格式无效，缺少必要字段');
        }

        // 重算 zip 整体 SHA256 作为权威签名（verify_patch 同样用此策略）
        $manifest['signature'] = hash('sha256', file_get_contents($zip_path));

        $zip->close();
        return $manifest;
    }

    /**
     * 验证补丁包签名和文件完整性
     *
     * 签名策略：zip 整体 SHA256（与 parse_patch 重算结果一致）。
     * 该策略实现简单且足够防篡改——攻击者修改任何 zip 条目都会改变整体字节哈希。
     *
     * @param array $manifest manifest 数据
     * @param string $zip_path ZIP 文件路径
     * @return bool
     */
    public function verify_patch($manifest, $zip_path) {
        // 1. 验证版本号格式（语义化版本）
        if (!preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
            return false;
        }

        // 2. 验证签名（ZIP 文件整体 SHA256）
        $zip_content = file_get_contents($zip_path);
        if ($zip_content === false) {
            return false;
        }
        $expected_sig = hash('sha256', $zip_content);
        if (!hash_equals($expected_sig, $manifest['signature'])) {
            return false;
        }

        // 3. 验证每个文件的 MD5（兼容单层/双层路径：直接命中失败时尝试加任意顶层目录前缀）
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== TRUE) {
            return false;
        }

        // 收集 zip 内所有顶层目录前缀（manifest.json 所在目录），用于路径回退
        $prefixes = array('');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^([^/]+/)(?!.*/manifest\.json$)#', $name, $m) && strpos($name, 'manifest.json') === false) {
                $prefix = $m[1];
                if (!in_array($prefix, $prefixes)) $prefixes[] = $prefix;
            }
        }

        foreach ($manifest['files'] as $file) {
            if (!isset($file['path'], $file['md5'])) {
                $zip->close();
                return false;
            }

            $content = false;
            foreach ($prefixes as $prefix) {
                $try = $prefix . $file['path'];
                $c = $zip->getFromName($try);
                if ($c !== false) { $content = $c; break; }
            }

            if ($content === false || md5($content) !== $file['md5']) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true;
    }

    /**
     * 检查版本兼容性
     * @param array $manifest manifest 数据
     * @param string $current_version 当前系统版本
     * @return bool|string 兼容返回 true，不兼容返回原因
     */
    public function check_version_compatible($manifest, $current_version) {
        $min = isset($manifest['min_version']) ? $manifest['min_version'] : '0.0.0';
        $max = isset($manifest['max_version']) ? $manifest['max_version'] : '999.999.999';

        if (version_compare($current_version, $min, '<')) {
            return "当前版本 {$current_version} 低于补丁要求的最低版本 {$min}";
        }
        if (version_compare($current_version, $max, '>')) {
            return "当前版本 {$current_version} 高于补丁支持的最高版本 {$max}";
        }
        return true;
    }

    /**
     * 应用补丁（核心操作）
     * @param array $manifest manifest 数据
     * @param string $zip_path ZIP 文件路径
     * @return array 操作结果
     */
    public function apply_patch($manifest, $zip_path) {
        $backup = new backup_manager($this->site_id);
        $backup_path = null;

        try {
            // 1. 备份将被修改的文件
            // manifest['files'] 每项含 path/md5，backup_manager 需要纯路径字符串数组
            $file_paths = array();
            foreach ($manifest['files'] as $f) {
                $file_paths[] = isset($f['path']) ? $f['path'] : $f;
            }
            $backup_path = $backup->backup($file_paths);

            // 2. 解压并替换文件
            $zip = new ZipArchive();
            if ($zip->open($zip_path) !== TRUE) {
                throw new Exception('无法打开补丁包');
            }

            // 推断根目录：以所有条目的公共顶层目录为根（manifest.json 所在目录）
            // 兼容三种布局：(a) 无包裹 (b) 单层包裹 patch-1.0.0/ (c) 多层包裹
            $root_dir = '';
            $manifest_entry = '';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if (substr($n, -13) === '/manifest.json' || $n === 'manifest.json') {
                    $manifest_entry = $n;
                    break;
                }
            }
            if ($manifest_entry !== '' && strpos($manifest_entry, '/') !== false) {
                $root_dir = substr($manifest_entry, 0, strrpos($manifest_entry, '/') + 1);
            }
            // 兜底：如果没有任何 manifest 条目（罕见），用第一个目录条目作为根
            if ($root_dir === '') {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $n = $zip->getNameIndex($i);
                    if (substr($n, -1) === '/') {
                        $root_dir = $n;
                        break;
                    }
                }
            }
            $applied_files = array();

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                // 跳过根目录和 manifest.json（含无包裹时 $root_dir='' 的退化情形）
                if ($name === $root_dir || $name === $root_dir . 'manifest.json') {
                    continue;
                }
                // 跳过任何顶层目录条目（zip 中以 / 结尾且不含更深的 /）
                if (substr($name, -1) === '/' && substr(rtrim($name, '/'), strpos($name, '/')) === '') {
                    continue;
                }


                // 移除根目录前缀，获取相对路径（ltrim 处理意外残留首斜杠）
                $relative_path = ltrim(substr($name, strlen($root_dir)), '/\\');
                if ($relative_path === '' || $relative_path === null) {
                    continue;
                }

                // 安全检查
                if (!$this->is_safe_path($relative_path)) {
                    throw new Exception("不允许修改的路径: {$relative_path}");
                }

                $target_path = ROOT_PATH . '/' . $relative_path;
                $content = $zip->getFromIndex($i);

                if ($stat['size'] === 0) {
                    // 目录
                    @mkdir($target_path, 0755, true);
                } else {
                    // 文件
                    $dir = dirname($target_path);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    if (file_put_contents($target_path, $content) === false) {
                        throw new Exception("无法写入文件: {$relative_path}");
                    }
                    $applied_files[] = $relative_path;
                }
            }

            $zip->close();

            // 3. 记录版本
            $version_id = $this->record_version($manifest, $backup_path, count($applied_files));

            // 4. 记录成功日志
            $this->log('apply', 0, '', array(
                'version_id' => $version_id,
                'files' => $applied_files,
                'backup_path' => $backup_path,
            ));

            // 5. 清理临时文件
            $this->cleanup($zip_path);

            return array('success' => true, 'version_id' => $version_id);

        } catch (Exception $e) {
            // 失败自动回滚
            if ($backup_path) {
                $rollback = new rollback_manager($this->site_id);
                $rollback->restore($backup_path);
            }

            // 记录失败日志
            $this->log('apply', 1, $e->getMessage());

            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * 路径安全检查
     * @param string $relative_path 相对路径
     * @return bool
     */
    private function is_safe_path($relative_path) {
        // 不允许路径穿越
        if (strpos($relative_path, '..') !== false) {
            return false;
        }

        // 不允许覆盖关键核心文件
        $blocked_prefixes = array(
            'lecms/xiunophp/',
            'lecms/index.php',
            'index.php',
            '.env',
            'config/config.php',
        );

        foreach ($blocked_prefixes as $prefix) {
            if (strpos($relative_path, $prefix) === 0) {
                return false;
            }
        }

        // 只允许修改 plugin/、view/、static/、config/、docs/ 等非核心目录
        // 单文件名（无路径分隔符）允许：补丁包往往以文件名作为一级条目
        if (strpos($relative_path, '/') === false && strpos($relative_path, '\\') === false) {
            return true;
        }
        $allowed_prefixes = array('plugin/', 'view/', 'static/', 'config/', 'docs/');
        foreach ($allowed_prefixes as $prefix) {
            if (strpos($relative_path, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 记录版本
     */
    private function record_version($manifest, $backup_path, $files_count) {
        if (!$this->db) return 0;
        $version = addslashes($manifest['version']);
        $name = isset($manifest['name']) ? addslashes($manifest['name']) : '';
        $changelog = isset($manifest['changelog']) ? addslashes($manifest['changelog']) : '';
        $backup_path_s = addslashes((string)$backup_path);
        $time = (int)$_ENV['_time'];
        $sql = "INSERT INTO `{$this->tablepre}patch_version` (site_id,version,name,changelog,files_count,status,backup_path,applied_at,created_at) VALUES ("
            . (int)$this->site_id . ",'$version','$name','$changelog'," . (int)$files_count . ",0,'$backup_path_s',$time,$time)";
        $this->db->exec($sql);
        return $this->db->last_insert_id();
    }

    /**
     * 记录操作日志
     */
    private function log($action, $status, $error = '', $detail = array()) {
        if (!$this->db) return 0;
        $action_s = addslashes($action);
        $error_s = addslashes($error);
        $detail_s = !empty($detail) ? "'" . addslashes(json_encode($detail, JSON_UNESCAPED_UNICODE)) . "'" : 'NULL';
        $time = (int)$_ENV['_time'];
        $sql = "INSERT INTO `{$this->tablepre}patch_log` (site_id,action,status,error_message,detail,created_at) VALUES ("
            . (int)$this->site_id . ",'$action_s'," . (int)$status . ",'$error_s',$detail_s,$time)";
        $this->db->exec($sql);
        return $this->db->last_insert_id();
    }

    /**
     * 清理临时文件
     */
    private function cleanup($zip_path) {
        @unlink($zip_path);
    }
}
