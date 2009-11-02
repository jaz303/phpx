<?php
namespace phpx;

require 'phpx.php';

$def = new ClassDef('Foo');

$def->with_access('private static', function($class) {
    $class->define_public_instance_variable('foo', 100);
    $class->define_variable('bar', 200);
});

echo $def->to_php();
?>