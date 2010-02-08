<?php
class ExamplesInterfaceDelegationTest extends ztest\UnitTestCase {
    public function test_interface_delegation() {
        parse_and_eval(dirname(__FILE__) . '/interface_delegation_test_interface.phpx');
        parse_and_eval(dirname(__FILE__) . '/interface_delegation_test_class.phpx');
        
        $a = new EIDTC_Addressable;
        assert_equal("message sent to foo@bar.com", $a->send_to("foo@bar.com"));
    }
}
?>