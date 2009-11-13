<?php
class ValueTest extends ztest\UnitTestCase
{
    public function test_values_php_representation_is_exported_php_value() {
        $tests = array(1, 'foo', null, true, false, array(1,2,3), array('foo' => 'bar'));
        foreach ($tests as $test) {
            $value = new phpx\Value($test);
            assert_equal(var_export($test, true), $value->to_php());
        }
    }
    
    public function test_value_returns_native_value() {
        $value = new phpx\Value(array(1,2,3));
        assert_equal(array(1,2,3), $value->get_native_value());
    }
}
?>