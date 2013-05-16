<?php

namespace Tinyfy;

function startsWith($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) == $needle;
}

function fixSlash($path)
{
    return endsWith($path, '/') ? $path : $path . '/';
}

if(!function_exists('gzdecode'))
{
    function gzdecode($data) 
    { 
       return gzinflate(substr($data,10,-8)); 
    }
}

/**
 * Tinyfy Bundle
 */
class Bundle
{
    protected $_type;
    protected $_id;
    protected $_files;
    protected static $_config;
    
    const GZIP_LEVEL = 6;
    
    public function __construct($files = null, $type = 'js')
    {
        if($type != 'js' && $type != 'css')
        {
            throw new Tinyfy_Exception('Invalid type: '.$type);
        }
        
        $this->_type = $type;
        
        if(is_string($files))
        {
            $this->_files = array($files);
            $this->_id = md5($files);
        }
        elseif(is_array($files))
        {
            $this->_files = $files;
            $fileList = implode(',', $files);
            $this->_id = md5($fileList);
        }
        elseif($files !== null)
        {
            throw new Tinfy_Exception('Array or String of files expected.');
        }
    }
    
    public static function fromId($id, $type)
    {
        $instance = new self(null, $type);
        $instance->_id = $id;
        return $instance;
    }
    
    public static function setConfig(array $config)
    {
        if(!isset($config['resourcePath'])) $config['resourcePath'] = array();
        if(!isset($config['cachePath'])) $config['cachePath'] = array();
        
        $resourceBase = dirname(__FILE__).'/';
        $cacheBase = $resourceBase . '/cache/';
        
        $config['resourcePath']['css'] = isset($config['resourcePath']['css']) ? fixSlash($config['resourcePath']['css']) : $resourceBase . 'css/';
        $config['resourcePath']['js']  = isset($config['resourcePath']['js'])  ? fixSlash($config['resourcePath']['js'])  : $resourceBase . 'js/';
        $config['cachePath']['css']    = isset($config['cachePath']['css'])    ? fixSlash($config['cachePath']['css'])    : $cacheBase . 'css/';
        $config['cachePath']['js']     = isset($config['cachePath']['js'])     ? fixSlash($config['cachePath']['js'])     : $cacheBase . 'js/';
        
        self::$_config = $config;
    }
    
    public static function getConfig()
    {
        if(self::$_config === null)
        {
            self::setConfig(array());
        }
        
        return self::$_config;
    }
    
    public function isDehydrated()
    {
        return $this->_files === null;
    }
    
    public function getFiles()
    {
        return $this->_files;
    }
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function getMimeType()
    {
        return $this->_type == 'js' ? 'text/javascript' : 'text/css';
    }
    
    protected function getAbsolutePath($gzip = true)
    {
        $config = self::getConfig();
        $p = ($this->_type == 'js') ? $config['cachePath']['js'] . $this->_id . '.js' : $config['cachePath']['css'] . $this->_id . '.css';
        if($gzip) $p .= '.gz';
        return $p;
    }
    
    public function getCacheMtime($gzip = true)
    {
        return @filemtime($this->getAbsolutePath($gzip));
    }
    
    protected function _save($data, $gzip = true)
    {
        $config = self::getConfig();
        if($gzip) $data = gzencode($data, isset($config['gzipLevel']) ? $config['gzipLevel'] : self::GZIP_LEVEL);
        return file_put_contents($this->getAbsolutePath($gzip), $data, LOCK_EX);
    }

    public function load($gzip = true, $decompress = false)
    {
        $data = @file_get_contents($this->getAbsolutePath($gzip));
        return ($gzip && $decompress) ? gzdecode($data) : $data;
    }
    
    public function compress($force = false, $noThrow = false)
    {
        if($this->isDehydrated())
        {
            throw new Exception('Can\'t compress a dehydrated bundle.');
        }
        
        $config = self::getConfig();
        
        $uMod = $this->getCacheMtime(false);
        $cMod = $this->getCacheMtime(true);
        
        $lastMod = max($uMod, $cMod);
        
        $skip = ($lastMod !== false) && !$force;
        
        $basePath = ($this->_type == 'js') ? $config['resourcePath']['js'] : $config['resourcePath']['css'];
        $suffix = ($this->_type == 'js') ? '.js' : '.css'; 
        
        foreach($this->_files as $i => &$file)
        {
            $file = $basePath . $file . $suffix;
            if(($mod = @filemtime($file)) !== false)
            {
                if($skip && ($mod > $lastMod))
                {
                    $skip = false;
                }
            }
            elseif($noThrow)
            {
                unset($this->_files[$i]);
            }
            else
            {
                throw new Exception('Resource '.$file.' doesn\'t exist.');
            }
        }
        
        if(!$skip)
        {
            $this->_compress($noThrow);
        }
        else
        {
            if($cMod < $uMod)
            {
                $this->_fixCompressed();
            }
            else if($cMod > $uMod)
            {
                $this->_fixUncompressed();
            }
        }
    }
    
    protected function _fixCompressed()
    {
        $this->_save($this->load(false), true);
        touch($this->getAbsolutePath(true), $this->getCacheMtime(false)); // fix mtime
    }
    
    protected function _fixUncompressed()
    {
        $this->_save($this->load(true, true), false);
        touch($this->getAbsolutePath(false), $this->getCacheMtime(true)); // fix mtime
    }
    
    protected function _compress($noThrow = false)
    {
        $config = self::getConfig();
        $compressor = null;
        
        if(!isset($config['backend'][$this->_type]))
        {
            $compressor = ($this->_type == 'js') ?  new \Tinyfy\Compressor\JS\ClosureCompressor(array()) : new \Tinyfy\Compressor\CSS\DefaultCompressor();
        }
        else if(is_object($config['backend'][$this->_type]) && $config['backend'][$this->_type] instanceof Compressor\AbstractCompressor)
        {
            $compressor = $config['backend'][$this->_type];
        }
        else
        {
            throw new Exception('Specified Backend must be instance of Tinyfy_Compressor');
        }
        
        $buffer = $compressor->compressBundle($this, $noThrow);
        
        $time = time();
        
        $this->_save($buffer, false); // uncompressed version
        $this->_save($buffer, true);  // gzip-version
        
        // ensure that both files have the same mtime!
        touch($this->getAbsolutePath(false), $time);
        touch($this->getAbsolutePath(true), $time);
    }
    
    public function getUrl($pattern = null)
    {
        $config = self::getConfig();
        $pattern = $pattern ? $pattern : @$config['urlPattern'][$this->_type];
        if(!$pattern)
        {
            throw new Tinyfy_Exception('Please specify a URL pattern for '.$this->_type.' files!');
        }
        
        $url = sprintf($pattern, $this->_id, $this->getCacheMtime());
        return $url;
    }
}
