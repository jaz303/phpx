<?php
class ExamplesAnnotationTest extends ztest\UnitTestCase {
    public function test_annotations() {
        parse_and_eval(dirname(__FILE__) . '/annotation_test_class.phpx');
        
        assert_equal(array('model' => true),
                     phpx\annotations_for('ExamplesAnnotationTestClass'));
 
        assert_equal(array('model' => true),
                     phpx\annotations_for(new ExamplesAnnotationTestClass));
                     
        assert_equal(array('foo' => 'bar'),
                     phpx\annotations_for('ExamplesAnnotationTestClass', '$baz'));

        assert_equal(array('foo' => 'bar'),
                     phpx\annotations_for(new ExamplesAnnotationTestClass, '$baz'));
                     
        assert_equal(array('access' => array('admin', 'public')),
                     phpx\annotations_for('ExamplesAnnotationTestClass', 'find_all'));

        assert_equal(array('access' => array('admin', 'public')),
                     phpx\annotations_for(new ExamplesAnnotationTestClass, 'find_all'));
        
    }
}
?>