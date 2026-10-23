<?php
/**
 * 真实使用统计 API（演示站）
 * GET stats-api.php?action=track&k=naming&n=李思辰   记录一次行为
 * GET stats-api.php?action=get                       读取统计 JSON
 * GET stats-api.php?action=reset                     （运维）清零
 * 行为类型：naming 起名 | score 打分 | match 配对 | search 搜索 | order 解锁订单
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0755, true); }
$FILE = $DATA_DIR . '/stats.json';

function load_stats($FILE) {
    if (!file_exists($FILE)) {
        return array('totals' => array('naming' => 0, 'score' => 0, 'match' => 0, 'search' => 0, 'order' => 0),
                     'recent' => array(), 'charUsage' => array(), 'orders' => array());
    }
    $j = json_decode(file_get_contents($FILE), true);
    return is_array($j) ? $j : array();
}

function save_stats($FILE, $s) {
    $fp = fopen($FILE, 'c');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($s, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'get';

if ($action === 'track') {
    $k = isset($_GET['k']) ? preg_replace('/[^a-z]/', '', $_GET['k']) : '';
    $n = isset($_GET['n']) ? trim($_GET['n']) : '';
    $allowed = array('naming', 'score', 'match', 'search', 'order');
    if (!in_array($k, $allowed)) { echo json_encode(array('ok' => 0, 'err' => 'bad k')); exit; }
    $n = mb_substr($n, 0, 24);
    $n = htmlspecialchars($n, ENT_QUOTES, 'UTF-8');   /* 纵深防御：入库前转义 HTML */
    $s = load_stats($FILE);
    $s['totals'][$k] = (isset($s['totals'][$k]) ? $s['totals'][$k] : 0) + 1;
    if ($n !== '' && $k !== 'order') {
        array_unshift($s['recent'], array('k' => $k, 'n' => $n, 't' => time()));
        if (count($s['recent']) > 60) $s['recent'] = array_slice($s['recent'], 0, 60);
        if ($k === 'naming' || $k === 'score') {
            $chars = preg_split('//u', $n, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $ch) {
                if (preg_match('/[\x{4e00}-\x{9fff}]/u', $ch)) {
                    $s['charUsage'][$ch] = (isset($s['charUsage'][$ch]) ? $s['charUsage'][$ch] : 0) + 1;
                }
            }
            if (count($s['charUsage']) > 800) { arsort($s['charUsage']); $s['charUsage'] = array_slice($s['charUsage'], 0, 500, true); }
        }
    }
    if ($k === 'order' && $n !== '') {
        array_unshift($s['orders'], array('no' => $n, 't' => time()));
        if (count($s['orders']) > 40) $s['orders'] = array_slice($s['orders'], 0, 40);
    }
    save_stats($FILE, $s);
    echo json_encode(array('ok' => 1, 'totals' => $s['totals']));
    exit;
}

if ($action === 'reset') { @unlink($FILE); echo json_encode(array('ok' => 1)); exit; }

/* get */
$s = load_stats($FILE);
echo json_encode($s, JSON_UNESCAPED_UNICODE);
