<?php
namespace phpx;

class Value
{
    private $value;
    
    public function __construct($value) {
        $this->value = $value;
    }
    
    public function get_native_value() { return $this->value; }
    
    public function to_php() {
        return var_export($this->value, true);
    }
}

class Literal
{
    private $literal;
    
    public function __construct($literal) {
        $this->literal = $literal;
    }
    
    public function to_php() {
        return $this->literal;
    }
}
?>