<?php
class ExamplesPatternMatchingTest extends ztest\UnitTestCase {
    public function test_pattern_matching() {
        parse_and_eval(dirname(__FILE__) . '/pattern_matching_test_class.phpx');
        
        $i = new ExamplesPatternMatchingTestClass;
        
        assert_equal('foo:render', $i->render_template('foo'));
        assert_equal('bar:display', $i->display_template('bar'));
        
        assert_equal('people', $i->find_people());
        assert_equal('managers', $i->find_managers());
        
        assert_equal('all:1', ExamplesPatternMatchingTestClass::find_all(1));
        assert_equal('one:2', ExamplesPatternMatchingTestClass::find_one(2));
        
        assert_equal('foo', ExamplesPatternMatchingTestClass::foo());
        assert_equal('bar', ExamplesPatternMatchingTestClass::bar());
        
    }
}
?>