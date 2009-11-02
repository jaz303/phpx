<?php
class Singleton
{
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new __CLASS__();
        }
        return self::$instance;
    }
}


class Foo
{
    // Automatic interface delegation
    private $foo implements Bar, Baz;
    
    // Mixins
    // Imports: constants, fields, instance methods
    include Enumerable;
    
    // Static mixins
    // Imports: constants, static fields, static methods
    include static Singleton;
    
    // Code evaluation
    eval(function($class) {
        
        $c->property('forename', 'trim');
        $c->property('')
        
        $c->define_constant('A_CONSTANT', 100);
        
        $c->with_modifiers('public static', function($c) {
            $c->define_method('foobar', '$a', '
                return strtoupper($a);
            ');
        });
        
        $c->define_method('foobar', '$a, $b, $c', '
            return $a + $b + $c;
        ');
        
        $c->mixin('FooBar');
        
        // yes, fucking macros
        $c->has_many('users');
        
    });
    
    // Pattern-matching
    public function "/^get_(foo|bar|baz)$"($args) {
        
    }
    
    
    
}

class ClassBuilder
{
    public function get_class_name() {}
    
    public function define_constant() {}
    public function define_method() {}
    public function mixin($mixin);
    
    public function __call($method, $args) {
        
    }
}

class HasManyMacro
{
    public function append_to($class, $args) {
        
        $association_name   = $args[0];
        $options            = $args[1];
        
        
        
        
        $class->define_method($association_name, <<<-METHOD
            
        METHOD
            if (!$this->)
        ');
        
    }
}



?>