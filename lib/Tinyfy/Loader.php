<?php

namespace Tinyfy;

class Loader
{
    protected static $_basePath;
    
    public static function register($basePath = null)
    {
        self::$_basePath = ($basePath !== null) ? realpath($basePath) : dirname(dirname(__FILE__));
        
        spl_autoload_register(array(__CLASS__, '_autoload'));
    }
    
    public static function _autoload($class)
    {
        $file = self::$_basePath . '/' . str_replace('\\', '/', $class) . '.php';
        return @include($file);
    }
}
