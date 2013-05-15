Tinyfy
======

A CSS/JS compression framework written in PHP.


## Requirements
* PHP 5.3+ (as Tinyfy uses namespaces)
* zlib (for compression)
* Google Closure Compiler (.jar file)


## Usage

### Step 1 - Autoloading:
Set up Autoloading (you might skip this step if you are already using an autoloader, e.g. the *Zend_Loader* classes that come shipped with the Zend Framework)
```php
<?php
require('../lib/Tinyfy/Loader.php');
\Tinyfy\Loader::register();
```

### Step 2 - Configuration:
Tinyfy needs to know where your JS/CSS resources are stored and where to put the output files.
You also need to specify a URL pattern to tell your browser how to reach them.
See the following example configuration:

```php
use Tinyfy\Tinyfy;

// Google Closure Compiler is the only JS backend available at the moment.
use Tinyfy\Compressor\JS\ClosureCompressor as JSC;

$tinyfyConfig = array(

  // URL patterns (%s is the md5 bundle id, %d is the last modified date - make sure to include it!)
  'urlPattern' => array(
    'js'  => '//assets1.jns.io/c/%s%d.js',
    'css' => '//assets1.jns.io/c/%s%d.css'),

  'cachePath' => array(
    'js'  => '/var/www/jns.io/static/_compiled',
    'css' => '/var/www/jns.io/static/_compiled'),
    
  'resourcePath' => array(
    'css' => '/var/www/jns.io/static/css',
    'js'  => '/var/www/jns.io/static/js')),

  // closure compiler configuration
  'backend' => array('js' =>
    new JSC(array(
    
      // since it's just a wrapper, you need to specify the path to the compiler's jar file.
      'jar' => '/var/www/jns.io/lib/closure-compiler/compiler.jar',
      
      // compilation level
      // COMPILATION_LEVEL_WHITESPACE_ONLY, COMPILATION_LEVEL_SIMPLE or COMPILATION_LEVEL_ADVANCED
      'compilationLevel' => JSC::COMPILATION_LEVEL_SIMPLE,
      
      // language version
      // LANG_ECMASCRIPT3, LANG_ECMASCRIPT5 or LANG_ECMASCRIPT5_STRICT
      'languageVersion' => JSC::LANG_ECMASCRIPT5))),
      
);
                                    
Tinyfy::bootstrap($tinyfyConfig);
                                    
```

### Step 3 - Defining Bundles:
All you need to know is that there are two helper methods, Tinyfy::css() and Tinyfy::js() which take care of compressing the bundle, generating the HTML markup etc:

```php
<?=Tinyfy::css(array('bootstrap.custom', 'fonts', 'fancybox/jquery.fancybox', 'main'))?>

<!-- which will output something like <link
rel="stylesheet" href="//assets1.jns.io/c/c4cbd5d8fe63d6ecc7e01c4ec23fcf3b1368643819.css"> -->

<?=Tinyfy::js(array('modernizr.custom', 'jquery.easing', 'jquery.fancybox.pack', 'main'))?>
<!-- <script src="//assets1.jns.io/c/de09618aa62ac7b8cd25e217385d1d491368641202.js"></script> -->

or to tell your browser to defer loading
<?=Tinyfy::js(array('modernizr.custom', 'jquery.easing', 'jquery.fancybox.pack', 'main'), true)?>
<!-- <script src="//assets1.jns.io/c/de09618aa62ac7b8cd25e217385d1d491368641202.js" defer></script> -->
```


### Step 4 - Serving the bundles:
There are several ways of serving them. I recommend to configure your webserver to serve the files directly, without employing PHP. See my nginx example configuration below:

```
server {
    listen 80;
    server_name ~^assets\d+.jns.io$;
    root /var/www/jns.io/static;
    charset utf-8;
    access_log off;

    location ~* \.(css|js)$ {
        gzip_static on;
        gzip_vary on;
        expires max;
        etag off;
        add_header Cache-Control public;
        rewrite  "^/c/(.{32}).*.css$" /_compiled/$1.css last;
        rewrite  "^/c/(.{32}).*.js$"  /_compiled/$1.js last;
    }

    location ~* \.(eot|ttf|woff)$ {
        add_header Access-Control-Allow-Origin *;
    }
}
```

If this isn't an option for you, there's a class called \Tinyfy\Server which is capable of serving your bundles as well. (See the examples/builtin-server/server.php example)
