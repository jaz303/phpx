<?php
class ArgumentListTest extends ztest\UnitTestCase
{
    public function setup() {
        $this->list = new phpx\ArgumentList;
        $this->list->push(new phpx\Argument('foo'));
        $this->list->push(new phpx\Argument('bar'));
    }
    
    public function test_arity() {
        assert_equal(2, $this->list->arity());
    }
    
    public function test_get_arguments_returns_array_of_arguments() {
        $args = $this->list->get_arguments();
        assert_equal(2, count($args));
        foreach ($args as $a) ensure($a instanceof phpx\Argument);
    }
    
    public function test_converting_arg_list_to_php_is_concatentation_of_args() {
        $args = $this->list->get_arguments();
        assert_equal("{$args[0]->to_php()}, {$args[1]->to_php()}", $this->list->to_php());
    }
}
?>