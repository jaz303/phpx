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
    
    public function test_class_name() {
        $this->def = new phpx\ClassDef("ClassDefName");
        $this->with_class(function($i, $r, $d) {
            assert_equal('ClassDefName', get_class($i));
            assert_equal('ClassDefName', $d->get_name());
        });
    }
    
    public function test_new_class_defs_have_no_namespace() {
        ensure(!$this->def->has_namespace());
    }
    
    public function test_namespace_can_be_set_and_retrieved() {
        $this->def->set_namespace('ns');
        assert_equal('ns', $this->def->get_namespace());
    }
    
    public function test_setting_namespace_alters_qualified_name() {
        $this->def = new phpx\ClassDef("ClassDefNamespaceQ");
        $this->def->set_namespace('Foo');
        assert_equal('\\Foo\\ClassDefNamespaceQ', $this->def->get_qualified_name());
    }
    
    public function test_namespace_is_applied() {
        // TODO: this is broken
        // $this->def->set_namespace('foo');
        //         $this->with_class(function($i, $r, $d) {
        //             assert_equal('foo\\' . $d->get_name(), get_class($i));
        //         });
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
    
    public function test_access_blocks_are_combined() {
        $this->def->with_access('public final static', function($c) {
            $c->with_access('protected instance', function($c) {
                $a = $c->current_access();
                ensure($a->is_protected());
                ensure(!$a->is_static());
                ensure($a->is_final());
            });
        });
    }
    
    //
    //
    
    public function test_constant_definition() {
        $this->def->define_constant('FOO', 15);
        $this->with_class(function($i, $r, $d) {
            assert_equal(15, eval('return ' . get_class($i) . '::FOO;'));
        });
    }
    
    public function test_method_definition() {
        $this->def->define_method('foo', '$a, $b', 'return $a + $b;');
        $this->with_class(function($i, $r, $d) {
            assert_equal(6, $i->foo(2, 4));
        });
    }
    
    public function test_variable_definition() {
        $this->def->define_variable('foo', 100);
        $this->with_class(function($i, $r, $d) {
            assert_equal(100, $i->foo);
        });
    }
    
    public function test_access_is_applied_to_added_members() {
        
        $this->def->set_abstract(true);
        
        $this->def->with_access('protected static abstract', function($c) {
            $c->define_method('foo', '');
            $c->define_variable('bar');
        });
        
        $this->with_class(function($i, $r, $d) {
            
            $methods = $r->getMethods();
            assert_equal(1, count($methods));
            ensure($methods[0]->isProtected());
            ensure($methods[0]->isStatic());
            ensure($methods[0]->isAbstract());
            
            $vars = $r->getProperties();
            assert_equal(1, count($vars));
            ensure($vars[0]->isProtected());
            ensure($vars[0]->isStatic());
            
        });
        
    }
    
    public function test_pattern_matching() {
        
        $this->def->add_pattern('/^(woof|arf)$/', 'return "dog";');
        $this->def->add_pattern('/^(purr|meow)$/', 'return "cat";');
        
        $this->with_class(function($i, $r, $d) {
            assert_equal('dog', $i->woof());
            assert_equal('dog', $i->arf());
            assert_equal('cat', $i->purr());
            assert_equal('cat', $i->meow());
            
            assert_throws('\\Exception', function() use($i) { $i->raaaaaar(); });
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