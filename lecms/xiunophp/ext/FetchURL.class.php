<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2023-12-23
 * Description: URL抓取
 */
class FetchURL{

    // https request
    public static function https_fetch_url($url, $user_agent = '', $post = '', $cookie = '', $timeout = 30, $deep = 0) {
        if(substr($url, 0, 5) == 'http:') {
            return self::fetch_url($url, $user_agent, $post, $cookie, $timeout, $deep);
        }
        $w = stream_get_wrappers();
        $allow_url_fopen = strtolower(ini_get('allow_url_fopen'));
        $allow_url_fopen = (empty($allow_url_fopen) || $allow_url_fopen == 'off') ? 0 : 1;
        if(extension_loaded('openssl') && in_array('https', $w) && $allow_url_fopen) {
            return file_get_contents($url);
        } elseif (!function_exists('curl_init')) {
            throw new Exception('server not installed curl.');
        }

        if($user_agent){
            $http_user_agent = $user_agent;
        }else{
            $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            empty($http_user_agent) && $http_user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $http_user_agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        if($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if($cookie) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $cookie"));
        }
        (!ini_get('safe_mode') && !ini_get('open_basedir')) && curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转, 安全模式不允许
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($ch);
        if(curl_errno($ch)) {
            throw new Exception('Errno'.curl_error($ch));//捕抓异常
        }
        if(!$data) {
            curl_close($ch);
            return '';
        }

        list($header, $data) = explode("\r\n\r\n", $data);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code == 301 || $http_code == 302) {
            $matches = array();
            preg_match('/Location:(.*?)\n/', $header, $matches);
            $url = trim(array_pop($matches));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $data = curl_exec($ch);
        }
        curl_close($ch);
        return $data;
    }

    // http request
    public static function fetch_url($url, $user_agent = '', $post = '', $cookie = '', $timeout = 10,$deep = 0) {
        if($deep > 5) throw new Exception('超出 fetch_url() 最大递归深度！');
        if(substr($url, 0, 5) == 'https') {
            return self::https_fetch_url($url, $user_agent, $post, $cookie, $timeout, $deep);
        }

        if($user_agent){
            $http_user_agent = $user_agent;
        }else{
            $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            empty($http_user_agent) && $http_user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';
        }

        $w = stream_get_wrappers();
        $allow_url_fopen = strtolower(ini_get('allow_url_fopen'));
        $allow_url_fopen = (empty($allow_url_fopen) || $allow_url_fopen == 'off') ? 0 : 1;
        if(function_exists('fsockopen')) {
            $limit = 2000000;
            $ip = '';
            $return = '';
            $matches = parse_url($url);
            $host = $matches['host'];
            $path = $matches['path'] ? $matches['path'].(!empty($matches['query']) ? '?'.$matches['query'] : '') : '/';
            $port = !empty($matches['port']) ? $matches['port'] : 80;

            if(empty($post)) {
                $out = "GET $path HTTP/1.0\r\n";
                $out .= "Accept: */*\r\n";
                $out .= "Accept-Language: zh-cn\r\n";
                $out .= "User-Agent: $http_user_agent\r\n";
                $out .= "Host: $host\r\n";
                $out .= "Connection: Close\r\n";
                $out .= "Cookie:$cookie\r\n\r\n";
            } else {
                $out = "POST $path HTTP/1.0\r\n";
                $out .= "Accept: */*\r\n";
                $out .= "Accept-Language: zh-cn\r\n";
                $out .= "User-Agent: $http_user_agent\r\n";
                $out .= "Host: $host\r\n";
                $out .= 'Content-Length: '.strlen($post)."\r\n";
                $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $out .= "Connection: Close\r\n";
                $out .= "Cache-Control: no-cache\r\n";
                $out .= "Cookie:$cookie\r\n\r\n";
                $out .= $post;
            }
            $host == 'localhost' && $ip = '127.0.0.1';
            $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
            if(!$fp) {
                return FALSE;
            } else {
                stream_set_blocking($fp, TRUE);
                stream_set_timeout($fp, $timeout);
                @fwrite($fp, $out);
                $status = stream_get_meta_data($fp);
                if(isset($status['timed_out']) && !$status['timed_out']) {
                    $starttime = time();
                    while (!feof($fp)) {
                        if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                            break;
                        } else {
                            if(strtolower(substr($header, 0, 9)) == 'location:') {
                                $location = trim(substr($header, 9));
                                return self::fetch_url($location, $http_user_agent, $post, $cookie, $timeout, $deep + 1);
                            }
                        }
                    }

                    $stop = false;
                    while(!feof($fp) && !$stop) {
                        $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                        $return .= $data;
                        if($limit) {
                            $limit -= strlen($data);
                            $stop = $limit <= 0;
                        }
                        if(time() - $starttime > $timeout) break;
                    }
                }
                @fclose($fp);
                return $return;
            }
        } elseif(function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $http_user_agent);
            if($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            if($cookie) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie', $cookie));
            }
            $data = curl_exec($ch);

            if(curl_errno($ch)) {
                throw new Exception('Errno'.curl_error($ch));//捕抓异常
            }
            if(!$data) {
                curl_close($ch);
                return '';
            }

            list($header, $data) = explode("\r\n\r\n", $data);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($http_code == 301 || $http_code == 302) {
                $matches = array();
                preg_match('/Location:(.*?)\n/', $header, $matches);
                $url = trim(array_pop($matches));
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $data = curl_exec($ch);
            }
            curl_close($ch);
            return $data;
        } elseif($allow_url_fopen && empty($post) && empty($cookie) && in_array('http', $w)) {
            // 尝试连接
            $opts = array ('http'=>array('method'=>'GET', 'timeout'=>$timeout));
            $context = stream_context_create($opts);
            $html = file_get_contents($url, false, $context);
            return $html;
        } else {
            return FALSE;
        }
    }

    // 多线程抓取数据，需要CURL支持，一般在命令行下执行，此函数收集互联网，由 xiuno 整理。
    public static function multi_fetch_url($urls = array(), $user_agent = '') {
        $data = $conn = array();

        if($user_agent){
            $http_user_agent = $user_agent;
        }else{
            $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            empty($http_user_agent) && $http_user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';
        }

        if(!function_exists('curl_multi_init')) {
            foreach($urls as $k=>$url) {
                $data[$k] = self::fetch_url($url, $http_user_agent);
            }
            return $data;
        }

        $multi_handle = curl_multi_init();
        foreach ($urls as $i => $url) {
            $conn[$i] = curl_init($url);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            $timeout = 3;
            curl_setopt($conn[$i], CURLOPT_CONNECTTIMEOUT, $timeout); // 超时 seconds
            curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn[$i], CURLOPT_USERAGENT, $http_user_agent);
            //curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);
            curl_multi_add_handle($multi_handle, $conn[$i]);
        }
        do {
            $mrc = curl_multi_exec($multi_handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK) {
            if (curl_multi_select($multi_handle) != - 1) {
                do {
                    $mrc = curl_multi_exec($multi_handle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($urls as $i => $url) {
            $data[$i] = curl_multi_getcontent($conn[$i]);
            curl_multi_remove_handle($multi_handle, $conn[$i]);
            curl_close($conn[$i]);
        }
        return $data;
    }
}