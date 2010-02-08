<?php
class AnnotationParserTest extends ztest\UnitTestCase
{
    public function test_annotation_parsing() {
    
        $string = <<<ANNOTATION
    /**
     * Implicit true:
     * :super_user
     *
     * :count = 100
     * :extensions = [1,2,3]
     * :extensions[] = 4
     * :access = { "jason": "allow", "captain hook": "deny" }
     */
ANNOTATION;

        $parser = new phpx\AnnotationParser();
        
        $annotes = $parser->parse($string);
        
        ensure($annotes['super_user']);
        assert_equal(100, $annotes['count']);
        assert_equal(array(1,2,3,4), $annotes['extensions']);
        assert_equal(array('jason' => 'allow', 'captain hook' => 'deny'), $annotes['access']);
    
    }
}
?>