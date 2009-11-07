<?php

define('BAR', 'baz');

function foobar() {
    return $x + 1;
}

abstract class Mooblio extends Bar implements Bleem, Baz {
    const FOO = 'abc';
    const BAR = 123;
    private $foo = 10;
    protected static $bar = null, $baz = 100;
    public $mince = array(1,2,3);
    public $void = true, $raa = false, $alfred = array(10, 20, 30);
    
    eval {
        $class->attr_accessor('forename', 'surname', 'title');
    }
    
    public static function foo() { return "foo"; }
    private function bar() {
        if (false) { return 1 + 2 + 3; } else {
            foreach (array(1,2,3) as $ix) {
                $arf += $ix;
            }
            return $zelda;
        }
    }
    protected abstract function zebedee(array $foo, Barf $baz = null);
    
    include static \Foo\Bar;
    
    private $test = null implements \test\Baz;
}

foreach ($x as $k => $v) {
    do {
         echo $k
    } while ($v--);
}
?>