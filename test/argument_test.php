<?php
class ArgumentTest extends ztest\UnitTestCase
{
    public function setup() {
        $this->arg = new phpx\Argument('arg');
    }

    public function test_name() {
        assert_equal('arg', $this->arg->get_name());
    }

    public function test_no_modifiers() {
        $this->expect('$arg');
        ensure(!$this->arg->is_array());
        ensure(!$this->arg->has_type());
        ensure(!$this->arg->is_null_allowed());
        ensure(!$this->arg->has_default());
        ensure(!$this->arg->is_reference());
    }

    public function test_array() {
        $this->arg->set_array(true);
        $this->expect('array $arg');
        ensure($this->arg->is_array());
    }

    public function test_type() {
        $this->arg->set_type('\\FooBar');
        $this->expect('\\FooBar $arg');
        ensure($this->arg->has_type());
        assert_equal('\\FooBar', $this->arg->get_type());
    }

    public function test_type_is_absolutized_when_converting_to_php_only() {
        $this->arg->set_type('FooBar');
        $this->expect('\\FooBar $arg');
        assert_equal('FooBar', $this->arg->get_type());
    }

    public function test_null_allowed() {
        $this->arg->set_type('\\FooBar');
        $this->arg->set_null_allowed(true);
        $this->expect('\\FooBar $arg = null');
        ensure($this->arg->is_null_allowed());
    }

    public function test_default() {
        $this->arg->set_default(5);
        $this->expect('$arg = 5');
        ensure($this->arg->has_default());
    }

    public function test_removing_default() {
        $this->arg->set_default(5);
        ensure($this->arg->has_default());
        $this->arg->remove_default();
        ensure(!$this->arg->has_default());
    }

    public function test_default_is_coerced_to_value_object() {
        $this->arg->set_default(array(1,2,3));
        ensure($this->arg->get_default() instanceof phpx\Value);
        assert_equal(array(1,2,3), $this->arg->get_default()->get_native_value());
    }

    public function test_reference() {
        $this->arg->set_reference(true);
        $this->expect('&$arg');
        ensure($this->arg->is_reference());
    }

    private function expect($outcome) {
        assert_equal($outcome, $this->arg->to_php());
    }
}
?>