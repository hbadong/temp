<?php
/**
 * request context → DB record
 */
class spider_recorder {
    public static function build_record($ctx) {
        $ua = (string)($ctx['ua'] ?? '');
        if (strlen($ua) > 512) $ua = substr($ua, 0, 512);
        $url = (string)($ctx['url'] ?? '');
        if (strlen($url) > 512) $url = substr($url, 0, 512);
        $ref = $ctx['referer'] ?? null;
        if ($ref !== null && strlen($ref) > 512) $ref = substr($ref, 0, 512);
        $ip_text = (string)($ctx['ip'] ?? '');
        $ip_bin  = @inet_pton($ip_text);
        if ($ip_bin === false) { $ip_bin = ''; $ip_text = ''; }
        return array(
            'site_id'          => (int)($ctx['site_id'] ?? 0),
            'engine'           => (string)($ctx['engine'] ?? 'other'),
            'ip'               => $ip_bin,
            'ip_text'          => $ip_text,
            'user_agent'       => $ua,
            'url'              => $url,
            'referer'          => $ref,
            'method'           => (string)($ctx['method'] ?? 'GET'),
            'response_code'    => (int)($ctx['response_code'] ?? 0),
            'response_size'    => (int)($ctx['response_size'] ?? 0),
            'duration_ms'      => (int)($ctx['duration_ms'] ?? 0),
            'is_blacklist_hit' => (int)($ctx['is_blacklist_hit'] ?? 0),
            'is_intercepted'   => (int)($ctx['is_intercepted'] ?? 0),
            'created_at'       => (int)($ctx['time'] ?? time()),
        );
    }
}
