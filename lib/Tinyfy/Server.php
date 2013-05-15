<?php

namespace Tinyfy;

use \Tinyfy\Util\HTTP as HTTPUtil;

class Server
{
    /*
    protected $_options;
    
    public function __construct(array $options = array())
    {
        $this->_options = $options;
    }
    */

    public function serve(Bundle $bundle, $forceUncompressed = false, $throw = false)
    {
        $lastModified = HTTPUtil::formatDate($bundle->getCacheMtime());
        
        $notModified = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastModified);
        
        if($notModified)
        {
            HTTPUtil::setResponseCode(304);
            HTTPUtil::setHeader('Expires', HTTPUtil::formatDate(time() + 31536000));
            HTTPUtil::setHeader('Last-Modified', $lastModified);
        }
        else
        {
            $compress = $forceUncompressed ? false : $this->_clientSupportsCompression();
            
            if(($data = $bundle->load($compress)) !== false)
            {
                HTTPUtil::setHeader('Content-Type', $bundle->getMimeType());
                if($compress) HTTPUtil::setHeader('Content-Encoding', 'gzip');
                HTTPUtil::setHeader('Content-Length', strlen($data));
                HTTPUtil::setHeader('Cache-Control', 'public, max-age=31536000');
                HTTPUtil::setHeader('Expires', HTTPUtil::formatDate(time() + 31536000));
                HTTPUtil::setHeader('Last-Modified', $lastModified);
                echo $data;
            }
            else
            {
                if($throw)
                {
                    throw new Exception('Loading bundle '.$this->_id.' failed.');
                }

                HTTPUtil::setResponseCode(404);
            }
        }
    }
}