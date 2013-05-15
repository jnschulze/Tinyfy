<?php

namespace Tinyfy;

class Tinyfy
{
    /**
     * HTML doctype
     */
    const DOCTYPE_HTML = 0;
    
    /**
     * XHTML doctype
     */
    const DOCTYPE_XHTML = 1;
    
    public static $docType = self::DOCTYPE_HTML;
    
    /**
     * Initialize Tinyfy
     *
     * @param  array $config (optional)
     * @return void
     */
    public static function bootstrap($config = array())
    {
        if(isset($config['docType'])) self::$docType = $config['docType'];
        
        Bundle::setConfig($config);
    }

    /**
     * Compress an array of CSS files
     *
     * @param  array  $files
     * @param  bool   $noThrow    (optional) - if true, don't throw exceptions in an error case
     * @param  string $urlPattern (optional) - an alternate URL pattern for this bundle
     * @return string
     */
    public static function css(array $files, $noThrow = false, $urlPattern = null)
    {
       $bundle = new Bundle($files, 'css');
       $bundle->compress(false, $noThrow);
       
       if(self::$docType == self::DOCTYPE_HTML)
       {
           return '<link rel="stylesheet" href="'.$bundle->getUrl($urlPattern).'">';
       }
       
       return '<link rel="stylesheet" href="'.$set->getUrl().'" />';
    }

    /**
     * Compress an array of JS files
     *
     * @param  array  $files
     * @param  bool   $defer      (optional) - if true, an defer attribute is added to the markup
     * @param  bool   $async      (optional) - if true, an async attribute is added to the markup 
     * @param  bool   $noThrow    (optional) - if true, don't throw exceptions in an error case
     * @param  string $urlPattern (optional) - an alternate URL pattern for this bundle
     * @return string
     */
    public static function js(array $files, $defer = false, $async = false, $noThrow = false, $urlPattern = null)
    {
        $bundle = new Bundle($files, 'js');
        $bundle->compress(false, $noThrow);
        
        $args = '';
        if($defer) $args .= ' defer';
        if($async) $args .= ' async';
        
        return '<script src="'.$bundle->getUrl($urlPattern).'"'.$args.'></script>';
    }
}
