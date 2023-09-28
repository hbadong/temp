<?php
/**
 * 封装的curl http请求类
 *
 * @package Http 
 * @version 1.2.0 
 * @since 20170917
 * @author edikud@163.com
 * @link http://115v.com
 * @copyright (c) edikud All Rights Reserved
 *
 */

class Http
{
    /**
     * Default params
     *
     * @var array
     */
    public static $defaultParams = array(
        'headers' => array(
		    'CURLOPT_USERAGENT' => 'Mozilla/5.0 (Windows NT 6.1; rv:23.0) Gecko/20100101 Firefox/23.0'
		),
        'timeout' => 15,
        'ssl'     => false,
        'opts'    => array(),
    );

    /**
     * Curl Http Info
     *
     * @var array
     */
    public static $info = array();

    /**
     * HTTP GET
     *
     * @param string $url
     * @param array $data
     * @param array $params
     * @return string
     */
    public static function get($url, $data = array(), $params = array())
    {
        if ($data) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($data);
        }

        return self::request($url, $params);
    }

    /**
     * HTTP POST
     *
     * @param string $url
     * @param array $data
     * @param array $params
     * @return string
     */
    public static function post($url, $data, $params = array())
    {
        $params['opts']['CURLOPT_POST']       = true;
        $params['opts']['CURLOPT_POSTFIELDS'] = http_build_query($data);
        return self::request($url, $params);
    }

    /**
     * HTTP request
     *
     * @param string $uri
     * @param array $params
     * @return string or throw Exception
     */
    public static function request($url, $params)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Can not find curl extension');
        }

        $curl = curl_init();
        $opts = self::initOpts($url, $params);
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);

        $errno = curl_errno($curl);
        $error = curl_error($curl);

        self::$info = curl_getinfo($curl) + array('errno' => $errno, 'error' => $error);

        if (0 !== $errno) {
            throw new Exception($error, $errno);
        }

        curl_close($curl);
        return $response;
    }

    /**
     * Init curl opts
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public static function initOpts($url, $params)
    {
        $params += self::$defaultParams;
        $opts = $params['opts'] + array(
            'CURLOPT_URL'            => $url,
			# 设置下载时间
            'CURLOPT_TIMEOUT'        => $params['timeout'],
			# 设置有返回信息，以流的形式返回，非不是直接输出
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_SSL_VERIFYPEER' => $params['ssl'],
        );

        if ($params['headers']) {
			# 设定header头
            $opts['CURLOPT_HTTPHEADER'] = $params['headers'];
        }

        return $opts;
    }
}