<?php
class AccessTestThing
{
    public static final function foo() {}
}

class AccessTest extends ztest\UnitTestCase
{
    public function setup() {
        $this->access = new phpx\Access;
    }
    
    public function test_new_access_is_clear() {
        ensure(!$this->access->has_access());
        ensure(!$this->access->has_final());
        ensure(!$this->access->has_abstract());
        ensure(!$this->access->has_static());
    }
    
    public function test_access_levels_work_sanely() {
        $levels = array('private', 'protected', 'public'); 
        foreach ($levels as $level) {
            $this->access->set_access($level);
            ensure($this->access->has_access());
            assert_equal($level, $this->access->get_access());
            foreach ($levels as $compare_level) {
                assert_equal($level == $compare_level, $this->access->{"is_{$compare_level}"}());
            }
            $this->access->{"set_{$level}"}();
            ensure($this->access->has_access());
            assert_equal($level, $this->access->get_access());
            foreach ($levels as $compare_level) {
                assert_equal($level == $compare_level, $this->access->{"is_{$compare_level}"}());
            }
            $this->access->clear_access();
            ensure(!$this->access->has_access());
        }
    }
    
    public function test_boolean_attributes_work_sanely() {
        $attribs = array('final', 'abstract', 'static');
        foreach ($attribs as $a) {
            $this->access->{"set_{$a}"}(false);
            ensure($this->access->{"has_{$a}"}());
            ensure(!$this->access->{"is_{$a}"}());
            $this->access->{"set_{$a}"}(true);
            ensure($this->access->{"has_{$a}"}());
            ensure($this->access->{"is_{$a}"}());
            $this->access->{"clear_{$a}"}();
            ensure(!$this->access->{"has_{$a}"}());
        }
    }
    
    public function test_coercion() {
        ensure(phpx\Access::coerce('public') instanceof phpx\Access);
        ensure(phpx\Access::coerce(new phpx\Access) instanceof phpx\Access);
        ensure(phpx\Access::coerce(new ReflectionMethod('AccessTestThing', 'foo')) instanceof phpx\Access);
    }
    
    public function test_parsing() {
        
        $a1 = new phpx\Access();
        phpx\Access::parse_string_into_access($a1, 'public abstract');
        ensure($a1->is_public());
        ensure($a1->is_abstract());
        ensure(!$a1->is_final());
        ensure(!$a1->is_static());
        
        $a2 = new phpx\Access();
        phpx\Access::parse_string_into_access($a2, 'protected final static');
        ensure($a2->is_protected());
        ensure(!$a2->is_abstract());
        ensure($a2->is_final());
        ensure($a2->is_static());
        
        $a3 = new phpx\Access();
        phpx\Access::parse_string_into_access($a3, 'private');
        ensure($a3->is_private());
        ensure(!$a3->is_abstract());
        ensure(!$a3->is_final());
        ensure(!$a3->is_static());
        
    }
    
    public function test_generation_from_reflection() {
        
        $r = new ReflectionMethod('AccessTestThing', 'foo');
        $a = phpx\Access::for_reflection($r);
        
        ensure($a->is_public());
        ensure($a->is_static());
        ensure($a->is_final());
        ensure(!$a->is_abstract());
        
    }
    
    public function test_php_generation() {
        
        // yes, this is relies on knowing the order that Access spits em out in...
        $tests = array(
            'public final',
            'private static',
            'abstract',
            'protected final static'
        );
        
        foreach ($tests as $test) {
            $access = new phpx\Access;
            foreach (explode(' ', $test) as $word) {
                $access->{"set_{$word}"}(true);
            }
            assert_equal($test, $access->to_php());
        }
        
    }
    
}
?>