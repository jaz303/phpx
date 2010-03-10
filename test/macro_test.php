<?php
class TestMacro
{
    public function test_macro($class, $name, $value) {
        $class->define_method($name, 'return ' . var_export($value, true) . ';');
    }
}

class TestMacro2
{
    public function test_macro_1($class, $name, $value) {
        $class->define_method($name, 'return ' . var_export($value, true) . ';');
    }
    
    public function test_macro_2($class, $name, $value) {
        $class->define_method($name, 'return ' . var_export($value, true) . ';');
    }
}

class MacroTest extends ztest\UnitTestCase
{
    public function test_macro() {
        
        phpx\Macro::register('test_macro', 'TestMacro');
        
        $def = new phpx\ClassDef("MacroTestClass");
        $def->test_macro("foo", "bar");
        $def->finalise();
        eval($def->to_php());
        
        $instance = new MacroTestClass;
        assert_equal('bar', $instance->foo());
        
    }
    
    public function test_auto_registration() {
        
        phpx\Macro::register('TestMacro2');
        
        $def = new phpx\ClassDef('MacroTestAutoRegClass');
        $def->test_macro_1('foo', 'bar');
        $def->test_macro_1('baz', 'bleem');
        $def->finalise();
        eval($def->to_php());
        
        $instance = new MacroTestAutoRegClass;
        assert_equal('bar', $instance->foo());
        assert_equal('bleem', $instance->baz());
        
    }
}
?>