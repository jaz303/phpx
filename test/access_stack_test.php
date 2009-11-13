<?php
class AccessStackTest extends ztest\UnitTestCase
{
    public function test_effective_access() {
        
        $s = new phpx\AccessStack;
        
        $s->push(new phpx\Access('public static'));
        $s->push(new phpx\Access('protected instance'));
        $s->push(new phpx\Access('private'));
        $s->pop();
        $s->push(new phpx\Access('final'));
        
        $a = $s->effective_access();
        
        ensure($a->is_protected());
        ensure(!$a->is_static());
        ensure($a->is_final());
        ensure(!$a->is_abstract());
        
    }
}
?>