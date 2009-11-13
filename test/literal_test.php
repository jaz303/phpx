<?php
class LiteralTest extends ztest\UnitTestCase
{
    public function test_literals_php_representation_is_identical_to_input() {
        $literal = new phpx\Literal("foo");
        assert_equal("foo", $literal->to_php());
    }
}
?>