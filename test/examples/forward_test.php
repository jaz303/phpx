<?php
class ExamplesForwardTest extends ztest\UnitTestCase {
    public function test_forward_declarations() {
        
        phpx\Forward::before('ExamplesForwardTestClass', function($class) {
            $class->define_public_instance_method('forward_1', 'return "forward_1";');
        });
        
        phpx\Forward::before('ExamplesForwardTestClass')
            ->define_public_instance_method('forward_2', 'return "forward_2";');
            
        phpx\Forward::after('ExamplesForwardTestClass', function($class) {
            $class->define_public_instance_method('after_1', 'return "after_1";');
        });

        phpx\Forward::after('ExamplesForwardTestClass')
            ->define_public_instance_method('after_2', 'return "after_2";');
        
        parse_and_eval(dirname(__FILE__) . '/forward_test_class.phpx');
        
        $item = new ExamplesForwardTestClass;
        
        assert_equal('forward_1', $item->forward_1());
        assert_equal('forward_2', $item->forward_2());
        
        assert_equal('after_1', $item->after_1());
        assert_equal('after_2', $item->after_2());
        
    }
}
?>