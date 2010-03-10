<?php
namespace phpx;

class ClassNotFoundException extends \Exception {}

class Library
{
    private static $classes = array();
    
    public static function register_file(SourceFile $file) {
        foreach ($file->get_defined_classes() as $class) {
            self::register_class($class);
        }
    }
    
    public static function register_class(ClassDef $class) {
        self::$classes[$class->get_qualified_name()] = $class;
    }
    
    public static function get_class_definition($class_name) {
        $class_name = absolutize_namespace($class_name);
        // class_exists will consult the autoloader, which should be configured
        // to delegate loading to phpx
        if (class_exists($class_name, true)) {
            if (isset(self::$classes[$class_name])) {
                return self::$classes[$class_name];
            } else {
                throw new ClassNotFoundException("Class $class_name is loaded, but not present in phpx registry");
            }
        } else {
            throw new ClassNotFoundException("Class $class_name does not exist");
        }
    }
    
    public static function lookup($class) {
        return self::get_class_definition(absolute_class_name($class));
    }
}
?>