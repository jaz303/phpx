<?php
// phpx example boot script

// load phpx runtime
require dirname(__FILE__)  . '/lib/phpx.php';

// autoloader should resolve class name to path and then pass it to phpx\PHPX::load()
function __autoload($class) {
    
    if ($class[0] == '\\') {
        $class = substr($class, 1);
    }
    
    $file = $class . '.php';
    
    phpx\PHPX::load($file);
}
?>