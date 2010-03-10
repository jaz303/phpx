<?php
namespace phpx;

class MacroNotFoundException extends \Exception {}

class Macro
{
    private static $macros      = array();
    private static $finalizers  = array();
    
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
    
    /**
     * Registers a finalizer to be run on a class definition just before it is
     * converted to PHP source. Any class implementing a public static <tt>finalize()</tt>
     * method is a valid finalizer.
     *
     * @param $class_name nome of finalizer class. Should be absolute.
     * @param $where where to place the finalizer in the chain. Either 'start' or 'end'
     */
    public static function register_finalizer($class_name, $where = 'end') {
        $class_name = absolutize_namespace($class_name);
        if ($where == 'start') {
            array_unshift(self::$finalizers, $class_name);
        } elseif ($where == 'end') {
            self::$finalizers[] = $class_name;
        }
    }
    
    public static function clear_finalizers() {
        self::$finalizers = array();
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
    
    public static function apply_finalizers(ClassDef $class) {
        foreach (self::$finalizers as $finalizer) {
            call_user_func(array($finalizer, 'finalize'), $class);
        }
    }
}

function register_macro($macro, $class_name) {
    Macro::register($macro, $class_name);
}
?>