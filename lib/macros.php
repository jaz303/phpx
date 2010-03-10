<?php
namespace phpx;

class MacroNotFoundException extends \Exception {}

class Macro
{
    private static $macros = array();
    
    public static function register($macro, $class_name) {
        self::$macros[$macro] = absolutize_namespace($class_name);
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