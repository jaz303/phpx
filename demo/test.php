<?php
require '../lib/phpx.php';

set_include_path(dirname(__FILE__) . '/classes');

function __autoload($class) {
    
    if ($class[0] == '\\') {
        $class = substr($class, 1);
    }
    
    $file = $class . '.php';
    require "phpx://$file";
}

$person = new OtherPerson;

$person->set_forename('Jason');
$person->set_address_1('17 Hutton');

$person->render_person(10);

var_dump($person);
?>