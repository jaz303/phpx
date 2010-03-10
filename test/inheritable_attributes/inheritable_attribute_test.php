<?php
class InheritableAttributeTest extends ztest\UnitTestCase {
    public function test_mixins() {
        parse_and_eval(dirname(__FILE__) . '/inheritable_attribute_test_1.phpx');
        parse_and_eval(dirname(__FILE__) . '/inheritable_attribute_test_2.phpx');
        parse_and_eval(dirname(__FILE__) . '/inheritable_attribute_test_3.phpx');

        $i1 = new IAT1Test1;
        assert_equal(1, $i1->get_static('a'));
        assert_equal(1, $i1->get_static('b'));
        assert_equal(1, $i1->get_static('c'));
        assert_equal(array('a' => 1, 'b' => 1, 'c' => 1), $i1->get_static('d'));
        
        $i2 = new IAT1Test2;
        assert_equal(1, $i2->get_static('a'));
        assert_equal(2, $i2->get_static('b'));
        assert_equal(2, $i2->get_static('c'));
        assert_equal(array('a' => 1, 'b' => 2, 'c' => 2), $i2->get_static('d'));
        
        $i3 = new IAT1Test3;
        assert_equal(1, $i3->get_static('a'));
        assert_equal(2, $i3->get_static('b'));
        assert_equal(3, $i3->get_static('c'));
        assert_equal(array('a' => 1, 'b' => 2, 'c' => 3), $i3->get_static('d'));
        
    }
}
?>