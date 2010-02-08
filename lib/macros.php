<?php
namespace phpx;

class MacroNotFoundException extends \Exception {}

class Macro
{
    private static $macros = array();
    
    public static function register($macro, $class_name) {
        self::$macros[$macro] = absolutize_namespace($class_name);
    }
    
    public static function apply($macro, ClassDef $class, $args) {
        
        if (!isset(self::$macros[$macro])) {
            throw new MacroNotFoundException;
        }
        
        $class_name = self::$macros[$macro];
        $macro = new $class_name;
        
        array_unshift($args, $class);
        call_user_func_array(array($macro, 'apply'), $args);
    
    }
}

function register_macro($macro, $class_name) {
    Macro::register($macro, $class_name);
}
?>