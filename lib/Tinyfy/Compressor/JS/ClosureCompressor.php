<?php

namespace Tinyfy\Compressor\JS;

class ClosureCompressor extends \Tinyfy\Compressor\AbstractCompressor
{
    const LANG_ECMASCRIPT3 = 'ECMASCRIPT3';
    const LANG_ECMASCRIPT5 = 'ECMASCRIPT5';
    const LANG_ECMASCRIPT5_STRICT = 'ECMASCRIPT5_STRICT';
    
    const COMPILATION_LEVEL_WHITESPACE_ONLY = 'WHITESPACE_ONLY';
    const COMPILATION_LEVEL_SIMPLE = 'SIMPLE_OPTIMIZATIONS';
    const COMPILATION_LEVEL_ADVANCED = 'ADVANCED_OPTIMIZATIONS';
    
    private $_options = array();
    
    public function __construct(array $options)
    {
        $this->_opts = $options;
    }
    
    private function _invoke(array $files)
    {
        if(empty($files))
        {
            throw new \Tinyfy\Exception('Files array mustn\'t be empty');
        }
        
        $jar = isset($this->_opts['jar']) ? $this->_opts['jar'] : 'compiler.jar';
        
        $command = 'java -jar '.$jar;
        $command .= ' --warning_level QUIET';
        
        if(isset($this->_opts['languageVersion']))
        {
            $command .= ' --language_in '.$this->_opts['languageVersion'];
        }
        
        if(isset($this->_opts['compilationLevel']))
        {
            $command .= ' --compilation_level '.$this->_opts['compilationLevel'];
        }
        
        if(isset($this->_opts['rawArgs']))
        {
            $command .= ' '.$this->_opts['rawArgs'];
        }
        
        $command .= ' --js '.implode(' ', $files);
        
        $descriptors = array(
           //0 => array('pipe', 'r'),
           1 => array('pipe', 'w'),
           2 => array('pipe', 'w')
        );

        $process = proc_open(escapeshellcmd($command), $descriptors, $pipes);
        
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        //fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $retcode = proc_close($process);
        
        if($retcode != 0)
        {
            throw new \Tinyfy\Exception('Calling closure compiler failed. Output: '.$stderr);
        }
        
        return $stdout;
    }
    
    public function compressBundle(\Tinyfy\Bundle $bundle, $noThrow = false)
    {
        try
        {
            return $this->_invoke($bundle->getFiles());
        }
        catch(Exception $e)
        {
            if(!$noThrow) throw $e;
        }
        
    }
}
