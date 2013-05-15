<?php

namespace Tinyfy\Compressor;

abstract class AbstractCompressor implements CompressorInterface
{   
    protected static function _loadFiles(array $files, $noThrow = false)
    {
        $buf = '';
        foreach($files as $f)
        {
            if(($data = @file_get_contents($f)) !== false)
            {
                $buf .= $data;
            }
            else if(!$noThrow)
            {
                throw new \Tinyfy\Exception('Resource "'.$f.'" doesn\'t exist');
            }
        }
        return $buf;
    }
}