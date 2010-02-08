<?php
class ConstantTest extends ztest\UnitTestCase
{
    public function setup() {
        $this->c = new phpx\Constant('foo', 10);
    }
    
    public function test_name_is_returned() {
        assert_equal('foo', $this->c->get_name());
    }
    
    public function test_value_is_coerced_to_value_object() {
        ensure($this->c->get_value() instanceof phpx\Value);
    }
    
    public function test_value_object_has_correct_native_value() {
        assert_equal(10, $this->c->get_value()->get_native_value());
    }
    
    public function test_php_representation() {
        assert_equal('const foo = 10;', $this->c->to_php());
    }
}
?>