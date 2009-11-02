<?php
foreach (token_get_all(file_get_contents('tokens.php')) as $tok) {
    if (is_array($tok)) {
        echo token_name($tok[0]);
    } else {
        echo $tok;
    }
    echo "\n";
}
?>