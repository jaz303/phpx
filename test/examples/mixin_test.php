<?php
class ExamplesMixinTest extends ztest\UnitTestCase {
    public function test_mixins() {
        parse_and_eval(dirname(__FILE__) . '/mixin_test_mixins.phpx');
        parse_and_eval(dirname(__FILE__) . '/mixin_test_classes.phpx');
        
        $thing = new EMT_Foo;
        assert_equal('foo', $thing->foo());
        assert_equal('bar', EMT_Foo::bar());
        assert_equal('baz', EMT_Foo::BAZ);
    }
}
?>