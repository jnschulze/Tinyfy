<?php

namespace Tinyfy\Compressor;

interface CompressorInterface
{
    public function compressBundle(\Tinyfy\Bundle $bundle, $noThrow = false);
}