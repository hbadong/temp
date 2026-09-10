<?php
class cookie{

    protected static $_defaultConfig = array(
        'expire' => 0,
        'path' => '/',
        'domain' => '',
        'secure'    => false, //  cookie 启用安全传输
        'httponly'  => false, // httponly 设置
        'setcookie' => true, // 是否使用 setcookie
    );

    //获取某个Cookie
    public static function get($cookieName = null, $prefix = null, $default = null){
        if (!$cookieName) {
            return isset($_COOKIE) ? $_COOKIE : null;
        }
        if (is_null($prefix)) $prefix = $_ENV['_config']['cookie_pre'];

        $cookieName = $prefix . $cookieName;
        return isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : $default;
    }

    //设置某个Cookie
    public static function set($name, $value, $option = null){
        if (!$name) return false;

        if (is_null($option)){
            $prefix = $_ENV['_config']['cookie_pre'];
            $expire = self::$_defaultConfig['expire'];
            $path = self::$_defaultConfig['path'];
            $domain = $_ENV['_config']['cookie_domain'];
            $secure = self::$_defaultConfig['secure'];
            $httponly = self::$_defaultConfig['httponly'];
            $setcookie = self::$_defaultConfig['setcookie'];
        }else{
            $prefix = isset($option['prefix']) ? $option['prefix'] : $_ENV['_config']['cookie_pre'];
            $expire = isset($option['expire']) ? time() + $option['expire'] : self::$_defaultConfig['expire'];
            $path = isset($option['path']) ? $option['path'] : self::$_defaultConfig['path'];
            $domain = isset($option['domain']) ? $option['domain'] : self::$_defaultConfig['domain'];
            $secure = isset($option['secure']) ? $option['secure'] : self::$_defaultConfig['secure'];
            $httponly = isset($option['httponly']) ? $option['httponly'] : self::$_defaultConfig['httponly'];
            $setcookie = isset($option['setcookie']) ? $option['setcookie'] : self::$_defaultConfig['setcookie'];
        }

        $name = $prefix . $name;
        if ($setcookie) {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
        $_COOKIE[$name] = $value;

        return true;
    }

    /**
     * 永久保存 Cookie 数据
     * @access public
     * @param  string $name   cookie 名称
     * @param  mixed  $value  cookie 值
     * @return void
     */
    public static function forever($name, $value = ''){
        self::set($name, $value, 315360000);
    }

    /**
     * 判断是否有 Cookie 数据
     * @access public
     * @param  string      $name   cookie 名称
     * @param  string|null $prefix cookie 前缀
     * @return bool
     */
    public static function has($name, $prefix = null){
        if (is_null($prefix)) $prefix = $_ENV['_config']['cookie_pre'];

        return isset($_COOKIE[$prefix . $name]);
    }

    //删除某个Cookie
    public static function delete($name, $prefix = null){
        if (!$name) return false;

        // 要删除的 cookie 前缀
        $prefix = !is_null($prefix) ? $prefix : $_ENV['_config']['cookie_pre'];

        $config = self::$_defaultConfig;
        if ($config['setcookie']) {
            setcookie($prefix . $name, '', $_SERVER['REQUEST_TIME'] - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        }

        unset($_COOKIE[$prefix . $name]);
        return true;
    }

    //删除所有Cookie
    public static function clear(){
        if (isset($_COOKIE)) {
            unset($_COOKIE);
        }
        return true;
    }

    /**
     * 清除指定前缀的所有 cookie
     * @access public
     * @param  string|null $prefix cookie 前缀
     * @return void
     */
    public static function clear_prefix($prefix = null){
        if (empty($_COOKIE)) {
            return;
        }

        // 要删除的 cookie 前缀
        $prefix = !is_null($prefix) ? $prefix : $_ENV['_config']['cookie_pre'];

        if ($prefix) {
            $config = self::$_defaultConfig;
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if ($config['setcookie']) {
                        setcookie($key, '', $_SERVER['REQUEST_TIME'] - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                    }

                    unset($_COOKIE[$key]);
                }
            }
        }

        return;
    }

}