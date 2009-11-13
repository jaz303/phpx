<?php
class FunctionsTest extends ztest\UnitTestCase
{
    public function test_absolutize_namespace_prepends_backlash_only_when_not_present() {
        assert_equal('\\foo\\bar', phpx\absolutize_namespace('foo\\bar'));
        assert_equal('\\foo\\bar', phpx\absolutize_namespace('\\foo\\bar'));
    }
    
    public function test_absolute_class_name_handles_strings() {
        assert_equal('\\foo\\bar', phpx\absolute_class_name('foo\\bar'));
    }
    
    public function test_absolute_class_name_handles_class_defs() {
        
    }
    
    public function test_absolute_class_name_handles_reflection_classes() {
        
    }
    
    public function test_value_or_object_returns_same_object() {
        $value = new phpx\Value(true);
        assert_identical($value, phpx\value_or_object($value));
    }
    
    public function test_value_or_object_returns_wrapping_value() {
        $object = phpx\value_or_object(5);
        ensure($object instanceof phpx\Value);
        assert_equal(5, $object->get_native_value());
    }
    
    public function test_literal_or_object_returns_same_object() {
        $value = new phpx\Literal("echo 'foo';");
        assert_identical($value, phpx\literal_or_object($value));
    }
    
    public function test_literal_or_object_returns_wrapping_value() {
        $object = phpx\literal_or_object("foreach");
        ensure($object instanceof phpx\Literal);
        assert_equal("foreach", $object->to_php());
    }
}
?>