<?php
namespace phpx;

class MacroNotFoundException extends \Exception {}

class Macro
{
    private static $macros = array();
    
    public static function register($macro, $class_name = null) {
        if ($class_name === null) {
            $class_name = $macro;
            $reflection = new \ReflectionClass($class_name);
            $methods = array();
            foreach ($reflection->getMethods() as $method) {
                if ($method->isPublic()) $methods[] = $method->getName();
            }
        } else {
            $methods = array($macro);
        }
        foreach ($methods as $m) {
            self::$macros[$m] = absolutize_namespace($class_name);
        }
    }
    
    public static function apply($macro_method, ClassDef $class, $args) {
        
        if (!isset(self::$macros[$macro_method])) {
            throw new MacroNotFoundException;
        }
        
        $class_name = self::$macros[$macro_method];
        $macro = new $class_name;
        
        array_unshift($args, $class);
        call_user_func_array(array($macro, $macro_method), $args);
    
    }
}

function register_macro($macro, $class_name) {
    Macro::register($macro, $class_name);
}
?>