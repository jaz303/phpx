<?php
namespace phpx;

class ArgumentList
{
    public static function create_from_reflection_method(\ReflectionMethod $m) {
        $args = new ArgumentList;
        foreach ($m->getParameters() as $p) {
            $args->push(Argument::create_from_reflection_parameter($p));
        }
        return $args;
    }
    
    private $arguments = array();
    
    public function arity() { return count($arguments); }
    public function get_arguments() { return $this->arguments; }
    
    public function push(Argument $arg) { $this->arguments[] = $arg; }
    
    public function to_php() {
        return implode(', ',
                    array_map(
                        function($i) { return $i->to_php(); },
                        $this->arguments
                    )
                );
    }
}

class Argument
{
    public static function create_from_reflection_parameter(\ReflectionParameter $rp) {
        
        $arg = new Argument($rp->getName());
        if ($rp->allowsNull()) {
            $arg->set_null_allowed(true);
        }
        
        if ($rp->isDefaultValueAvailable()) {
            $arg->set_default($rp->getDefaultValue());
        }
        
        if ($rp->isArray()) {
            $arg->set_type('array');
        } elseif ($type = $rp->getClass()) {
            $arg->set_type($type->getName());
        }
        
        if ($rp->isPassedByReference()) {
            $arg->set_reference(true);
        }
        
        return $arg;
    }
    
    private $name;
    
    private $type               = null;
    private $null_allowed       = false;
    private $has_default        = false;
    private $default            = null;
    private $reference          = false;
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    public function get_name() { return $this->name; }
    
    public function has_type() { return $this->type !== null; }
    public function get_type() { return $this->type; }
    public function set_type($t) { $this->type = $t; }
    
    public function is_null_allowed() { return $this->null_allowed; }
    public function set_null_allowed($n) { $this->null_allowed = (bool) $n; }
    
    public function has_default() { return $this->has_default; }
    public function get_default() { return $this->default; }
    public function remove_default() { $this->has_default = false; }
    public function set_default($d) {
        $this->default = value_or_object($d);
        $this->has_default = true;
    }
    
    public function is_reference() { return $this->reference; }
    public function set_reference($r) { $this->reference = (bool) $r; }
    
    public function to_php() {
        $out = '';
        
        if ($this->has_type()) {
            $out .= $this->get_type() . ' ';
        }
        
        if ($this->is_reference()) {
            $out .= '&';
        }
        
        $out .= '$' . $this->get_name();
        
        if ($this->has_default()) {
            $out .= ' = ' . $this->get_default()->to_php();
        } elseif ($this->has_type() && $this->is_null_allowed()) {
            $out .= ' = null';
        }
        
        return $out;
    }
}
?>