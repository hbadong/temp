<?php
defined('ROOT_PATH') || exit;

/**
 * TemplateComponent 注册表
 * 统一入口：TemplateComponent::render($name, $params)
 */
class TemplateComponent
{
    private static $components = [];
    private static $loaded = false;

    public static function render($name, $params = [])
    {
        if (!self::$loaded) {
            self::loadComponents();
        }

        $class = isset(self::$components[$name]) ? self::$components[$name] : null;
        if (!$class || !class_exists($class)) {
            return '';
        }

        $instance = new $class();
        return $instance->render($params);
    }

    private static function loadComponents()
    {
        $dir = dirname(__DIR__, 3) . '/plugin/template_manager/component/';
        if (!is_dir($dir)) return;

        $files = glob($dir . '*.php');
        foreach ($files as $file) {
            $name = str_replace('.php', '', basename($file));
            $class = ucfirst($name);
            self::$components[$name] = $class;
        }

        self::$loaded = true;
    }
}
