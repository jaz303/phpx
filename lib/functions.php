<?php
namespace phpx;

function absolutize_namespace($ns) {
    if ($ns[0] != '\\') $ns = '\\' . $ns;
    return $ns;
}

function absolute_class_name($class) {
    if ($class instanceof ClassDef) {
        $class = $class->get_qualified_name();
    } elseif ($class instanceof \ReflectionClass) {
        $class = $class->getName();
    }
    return absolutize_namespace((string) $class);
}

function value_or_object($thing) {
    return is_object($thing) ? $thing : new Value($thing);
}

function literal_or_object($thing) {
    return is_object($thing) ? $thing : new Literal($thing);
}
?>