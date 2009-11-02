<?php
$deps = array('access', 'source', 'arguments', 'parser');
foreach ($deps as $d) require dirname(__FILE__) . "/{$d}.php";
unset($deps);
?>