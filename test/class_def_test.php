<?php
class ClassDefParent {}
interface ClassDefIface1 {}
interface ClassDefIface2 {}

class ClassDefTest extends ztest\UnitTestCase
{
    private static $index = 0;
    
    public function setup() {
        $ix = ++self::$index;
        $this->def = new phpx\ClassDef("ClassDefTest{$ix}");
    }
    
    public function test_final_modifier_is_applied() {
        $this->def->set_final(true);
        $this->with_class(function($i, $r, $d) {
            ensure($r->isFinal());
            ensure($d->is_final());
        });
    }
    
    public function test_abstract_modifier_is_applied() {
        $this->def->set_abstract(true);
        $this->with_class(function($i, $r, $d) {
            ensure($r->isAbstract());
            ensure($d->is_abstract());
        });
    }
    
    public function test_superclass_is_applied() {
        $this->def->extend('ClassDefParent');
        $this->with_class(function($i, $r, $d) {
            assert_equal('ClassDefParent', get_parent_class($i));
            assert_equal('ClassDefParent', $d->get_superclass());
        });
    }
    
    public function test_interfaces_are_implemented() {
        $this->def->implement('ClassDefIface1');
        $this->def->implement('ClassDefIface2');
        $this->def->implement('ClassDefIface2');
        $this->with_class(function($i, $r, $d) {
            ensure(is_a($i, 'ClassDefIface1'));
            ensure(is_a($i, 'ClassDefIface2'));
            assert_equal(2, count($d->get_interfaces()));
        });
    }
    
    //
    //
    
    public function test_attr_accessor() {
        $this->def->attr_accessor('foo');
        $this->with_class(function($i, $r, $d) {
            $i->set_foo(10);
            assert_equal(10, $i->get_foo());
        });
    }
    
    //
    //
    
    private function with_class($lambda) {
        $name = $this->def->get_qualified_name();
        $this->def->finalise();
        eval($this->def->to_php());
        $reflection = new ReflectionClass($name);
        $instance = $reflection->isAbstract() ? null : (new $name);
        $lambda($instance, $reflection, $this->def);
    }
}
?>