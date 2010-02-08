<?php
namespace phpx;

function value_or_object($thing) {
    return is_object($thing) ? $thing : new Value($thing);
}

function literal_or_object($thing) {
    return is_object($thing) ? $thing : new Literal($thing);
}
?>