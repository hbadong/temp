<?php
/**
 * AC 自动机（Aho-Corasick）多模式匹配引擎
 *
 * 用于高效检测文本中的敏感词，支持：
 * - O(m) 构建（m = 所有模式串总长度）
 * - O(n) 匹配（n = 文本长度）
 * - 重叠词匹配
 * - 大小写不敏感
 * - 多字节字符（UTF-8）
 */

if (!defined('ROOT_PATH')) {
    die('Direct access is not allowed.');
}

class ac_automaton {

    /** @var array Trie 节点池，每个节点: ['children'=>[], 'fail'=>int, 'output'=>[]] */
    private $trie = [];

    /** @var int 当前节点数 */
    private $nodeCount = 0;

    /**
     * 构建 trie 树和 fail 跳转表
     *
     * @param array $patterns 词库数组，每个元素包含 word, level, category
     */
    public function build($patterns) {
        // 重置状态
        $this->trie      = [];
        $this->nodeCount = 0;
        $this->addNode(); // root = 0

        if (empty($patterns)) {
            return;
        }

        // --- Phase 1: 构建 Trie ---
        foreach ($patterns as $p) {
            $word    = isset($p['word'])    ? $p['word']    : '';
            $level   = isset($p['level'])   ? (int)$p['level']   : 1;
            $category = isset($p['category']) ? $p['category'] : '';

            if ($word === '') {
                continue;
            }

            $lowerWord = mb_strtolower($word, 'UTF-8');
            $node      = 0; // start from root

            $chars = $this->mb_str_split($lowerWord);
            foreach ($chars as $ch) {
                if (!isset($this->trie[$node]['children'][$ch])) {
                    $this->trie[$node]['children'][$ch] = $this->addNode();
                }
                $node = $this->trie[$node]['children'][$ch];
            }

            // 在终止节点记录输出（支持同一词库不同级别同词，保留最高级别）
            $existing = isset($this->trie[$node]['output'][0]) ? $this->trie[$node]['output'][0] : null;
            if ($existing === null || $level > $existing['level']) {
                $this->trie[$node]['output'] = [[
                    'word'    => $word,
                    'level'   => $level,
                    'category' => $category,
                ]];
            }
        }

        // --- Phase 2: BFS 构建 fail 跳转表 ---
        $queue = [];

        // root 的深度=1 子节点 fail 指向 root
        foreach ($this->trie[0]['children'] as $ch => $child) {
            $this->trie[$child]['fail'] = 0;
            $queue[] = $child;
        }

        while (!empty($queue)) {
            $current = array_shift($queue);

            foreach ($this->trie[$current]['children'] as $ch => $child) {
                // 找 fail 跳转：从 current.fail 开始沿 fail 链找能匹配 ch 的祖先
                $fail = $this->trie[$current]['fail'];
                while ($fail !== 0 && !isset($this->trie[$fail]['children'][$ch])) {
                    $fail = $this->trie[$fail]['fail'];
                }
                if (isset($this->trie[$fail]['children'][$ch])) {
                    $this->trie[$child]['fail'] = $this->trie[$fail]['children'][$ch];
                } else {
                    $this->trie[$child]['fail'] = 0;
                }

                // 合并输出：当前节点的 output 加上 fail 指向节点的 output
                if (!empty($this->trie[$this->trie[$child]['fail']]['output'])) {
                    $this->trie[$child]['output'] = array_merge(
                        $this->trie[$child]['output'],
                        $this->trie[$this->trie[$child]['fail']]['output']
                    );
                }

                $queue[] = $child;
            }
        }
    }

    /**
     * 搜索文本中的敏感词
     *
     * @param string $text 待检测文本
     * @return array 命中词列表，每个元素包含 word, level, position
     */
    public function search($text) {
        if ($text === '' || $this->nodeCount === 0) {
            return [];
        }

        $hits = [];
        $node = 0; // current state
        $len  = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $ch = mb_strtolower(mb_substr($text, $i, 1, 'UTF-8'), 'UTF-8');

            // 沿 fail 链找到能匹配 ch 的状态
            while ($node !== 0 && !isset($this->trie[$node]['children'][$ch])) {
                $node = $this->trie[$node]['fail'];
            }
            if (isset($this->trie[$node]['children'][$ch])) {
                $node = $this->trie[$node]['children'][$ch];
            } else {
                $node = 0;
            }

            // 输出当前节点及其 fail 链上所有命中
            if (!empty($this->trie[$node]['output'])) {
                foreach ($this->trie[$node]['output'] as $out) {
                    // position 是匹配词的起始位置
                    $wordLen = mb_strlen($out['word'], 'UTF-8');
                    $hits[] = [
                        'word'     => $out['word'],
                        'level'    => $out['level'],
                        'position' => $i - $wordLen + 1,
                    ];
                }
            }
        }

        return $hits;
    }

    /**
     * 根据级别策略处理文本
     *
     * @param string $text 待处理文本
     * @return array 包含 text（处理后文本）和 blocked（是否拒绝）
     */
    public function replace($text) {
        if ($text === '') {
            return ['text' => '', 'blocked' => false];
        }

        $hits = $this->search($text);

        if (empty($hits)) {
            return ['text' => $text, 'blocked' => false];
        }

        // 统一处理：收集所有需要替换的词（level >= 2）
        $replaceHits = [];
        $hasLevel3   = false;

        foreach ($hits as $hit) {
            if ($hit['level'] >= 2) {
                $replaceHits[] = $hit;
            }
            if ($hit['level'] == 3) {
                $hasLevel3 = true;
            }
        }

        if (empty($replaceHits)) {
            // 只有 level 1 命中，不处理
            return ['text' => $text, 'blocked' => false];
        }

        // 处理策略：level 3 拒绝 → 返回原文；level 2 替换
        if ($hasLevel3) {
            return ['text' => $text, 'blocked' => true];
        }

        // 去重并按起始位置排序（相同起始位置保留最长匹配）
        usort($replaceHits, function ($a, $b) {
            if ($a['position'] !== $b['position']) {
                return $a['position'] - $b['position'];
            }
            $aLen = mb_strlen($a['word'], 'UTF-8');
            $bLen = mb_strlen($b['word'], 'UTF-8');
            return $bLen - $aLen; // 长的优先
        });

        // 去重：跳过与前一个区间重叠的匹配
        $deduped = [];
        $lastEnd = -1;
        foreach ($replaceHits as $hit) {
            $start = $hit['position'];
            $end   = $start + mb_strlen($hit['word'], 'UTF-8');
            if ($start > $lastEnd) {
                $deduped[] = $hit;
                $lastEnd   = $end;
            }
        }

        // 从后往前替换（避免位置偏移问题）
        $MASK = '***';
        $result = $text;
        for ($i = count($deduped) - 1; $i >= 0; $i--) {
            $hit   = $deduped[$i];
            $start = $hit['position'];
            $len   = mb_strlen($hit['word'], 'UTF-8');
            $result = mb_substr($result, 0, $start, 'UTF-8')
                    . $MASK
                    . mb_substr($result, $start + $len, null, 'UTF-8');
        }

        return ['text' => $result, 'blocked' => $hasLevel3];
    }

    // ============================================================
    // 内部辅助方法
    // ============================================================

    /**
     * 添加新节点，返回节点 ID
     */
    private function addNode() {
        $this->trie[] = [
            'children' => [],
            'fail'     => 0,
            'output'   => [],
        ];
        return $this->nodeCount++;
    }

    /**
     * 多字节字符串分割为字符数组
     */
    private function mb_str_split($str) {
        $chars = [];
        $len   = mb_strlen($str, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $chars[] = mb_substr($str, $i, 1, 'UTF-8');
        }
        return $chars;
    }
}
