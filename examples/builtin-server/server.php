<?php

require dirname(__FILE__).'/Tinyfy/Loader.php';

\Tinyfy\Loader::register();

\Tinyfy\Tinyfy::bootstrap(array('cachePath' => array('js' => '/var/www/jns.io/static/_compiled', 'css' => '/var/www/jns.io/static/_compiled')));

// Variant A, passsing ?id= as a query string
$id = @$_GET['id'];

// Variant B, even shorter, omitting the 'id' param
/*
$getKeys = array_keys($_GET);
$id = @$getKeys[0];
*/

// get the bundle id depending on your pattern
// the default is [md5-hash].[mtime]
// so just use the first 32 bytes
$id = substr($id, 0, 32);

if(strlen($id) < 32)
{
    \Tinyfy\Util\HTTP::setResponseCode(404);
}

// get the type (css/js)
$type = isset($_GET['t']) ? $_GET['t'] : 'css';

// force uncompressed serving?
$forceUncompressed = isset($_GET['u']) && $_GET['u'] == 'true';

// create a bundle instance using our bundle id
$bundle = \Tinyfy\Bundle::fromId($id, $type);

// create a server instance
$server = new \Tinyfy\Server();

// here we go!
$server->serve($bundle, $forceUncompressed);
