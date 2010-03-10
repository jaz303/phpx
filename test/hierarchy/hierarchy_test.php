<?php
class HierarchyTest extends ztest\UnitTestCase {
    public function test_mixins() {
        parse_and_eval(dirname(__FILE__) . '/hierarchy_test_1.phpx');
        parse_and_eval(dirname(__FILE__) . '/hierarchy_test_2.phpx');
        parse_and_eval(dirname(__FILE__) . '/hierarchy_test_3.phpx');
        
        $class_def = \phpx\Library::get_class_definition('\\HHT3');
        
        $hierarchy = $class_def->get_class_hierarchy();
        
        assert_equal(3, count($hierarchy));
        assert_equal('\\HHT1', $hierarchy[0]->get_qualified_name());
        assert_equal('\\HHT2', $hierarchy[1]->get_qualified_name());
        assert_equal('\\HHT3', $hierarchy[2]->get_qualified_name());
        
    }
}
?>