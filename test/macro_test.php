<?php
class TestMacro
{
    public function apply($class, $name, $value) {
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
}
?>