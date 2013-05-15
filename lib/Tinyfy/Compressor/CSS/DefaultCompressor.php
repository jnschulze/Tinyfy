<?php

namespace Tinyfy\Compressor\CSS;

class DefaultCompressor extends \Tinyfy\Compressor\AbstractCompressor
{
    /**
     * Compressor code by Manas Tungare, http://manas.tungare.name/software/css-compression-in-php/
     */
    public static function compressBuffer($buf)
    {
        // Remove Comments
        $buf = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buf);

        // Remove space after colons
        $buf = str_replace(': ', ':', $buf);

        // Remove whitespace
        return str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buf);
    }
    
    public function compressBundle(\Tinyfy\Bundle $bundle, $noThrow = false)
    {
        $buffer = self::_loadFiles($bundle->getFiles());
        return self::compressBuffer($buffer);
    }
}
