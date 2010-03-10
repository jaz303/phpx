<?php
class ECET_GreetMacro {
    public function ecet_greet($class, $greeting) {
        $class->define_public_instance_method("greet", '$name', "return \"$greeting \$name\";");
    }
}

class ExamplesClassEvalTest extends ztest\UnitTestCase {
    public function test_mixins() {
        phpx\register_macro('ecet_greet', 'ECET_GreetMacro');
        
        parse_and_eval(dirname(__FILE__) . '/class_eval_test_class.phpx');
        
        assert_equal(15, ExamplesClassEvalTestClass::add(10, 5));
        
        $foo = new ExamplesClassEvalTestClass;
        $foo->set_foo('hello world');
        assert_equal('HELLO WORLD', $foo->get_foo());
        
        assert_equal('Hello Jason', $foo->greet('Jason'));
    }
}
?>