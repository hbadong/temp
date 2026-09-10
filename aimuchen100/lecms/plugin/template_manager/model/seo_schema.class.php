<?php
/**
 * SeoSchema 模型 — 5 类结构化数据生成
 *
 * generate($type, $data): string — 输出 JSON-LD 格式
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 4) . '/');
}

class SeoSchema
{
    /**
     * 生成 Schema 标记
     */
    public static function generate($type, $data = [])
    {
        if (!is_array($data)) {
            $data = [];
        }

        switch ($type) {
            case 'VideoGame':
                return self::videoGame($data);
            case 'Review':
                return self::review($data);
            case 'SoftwareApplication':
                return self::softwareApplication($data);
            case 'Article':
                return self::article($data);
            case 'ItemList':
                return self::itemList($data);
            default:
                return '';
        }
    }

    /**
     * VideoGame Schema — 游戏详情页
     */
    private static function videoGame($data)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => $data['name'] ?? '',
            'description' => self::truncate($data['description'] ?? '', 500),
            'applicationCategory' => 'Game',
            'operatingSystem' => $data['platform'] ?? 'Windows',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'CNY',
            ],
        ];

        if (isset($data['rating']) && $data['rating'] > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float)$data['rating'],
                'bestRating' => 5,
            ];
        }

        return self::toJson($schema);
    }

    /**
     * Review Schema — 评测页
     */
    private static function review($data)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'VideoGame',
                'name' => $data['game_name'] ?? '',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (float)($data['rating'] ?? 0),
                'bestRating' => 5,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? '匿名',
            ],
            'datePublished' => $data['date_published'] ?? date('Y-m-d'),
        ];

        return self::toJson($schema);
    }

    /**
     * SoftwareApplication Schema — 下载页
     */
    private static function softwareApplication($data)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $data['name'] ?? '',
            'description' => self::truncate($data['description'] ?? '', 500),
            'applicationCategory' => 'Game',
            'operatingSystem' => $data['platform'] ?? 'Windows',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'CNY',
            ],
        ];

        return self::toJson($schema);
    }

    /**
     * Article Schema — 攻略页
     */
    private static function article($data)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $data['headline'] ?? $data['title'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? '匿名',
            ],
            'dateModified' => $data['date_modified'] ?? date('Y-m-d'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $data['url'] ?? '',
            ],
        ];

        return self::toJson($schema);
    }

    /**
     * ItemList Schema — 排行榜
     */
    private static function itemList($data)
    {
        $items = isset($data['items']) ? $data['items'] : [];
        $itemListElement = [];

        foreach ($items as $i => $item) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => $item['url'] ?? '',
                'name' => $item['name'] ?? '',
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $itemListElement,
            'numberOfItems' => count($items),
        ];

        return self::toJson($schema);
    }

    /**
     * 转义并输出 JSON
     */
    private static function toJson($data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 截断文本
     */
    private static function truncate($text, $max = 500)
    {
        if (mb_strlen($text, 'UTF-8') <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max - 3, 'UTF-8') . '...';
    }
}
