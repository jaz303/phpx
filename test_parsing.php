<?php
namespace test {
    class Bar {}
    interface Baz {
        public function bleem(array $a, &$b, $c = 10);
        public function raa(Baz $b = null);
    }
    
    require 'phpx.php';

    $p = new \phpx\Parser();
    $r = $p->parse(file_get_contents('tokens.php'));

    echo $r->to_php();
}
?>