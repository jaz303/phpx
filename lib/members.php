<?php
namespace phpx;

class Constant
{
    private $name;
    private $value;
    
    public function __construct($name, $value) {
        $this->name = $name;
        $this->set_value($value);
    }
    
    public function get_name() { return $this->name; }
    
    public function get_value() { return $this->value; }
    public function set_value($v) { $this->value = value_or_object($v); }
    
    public function to_php() {
        return "const {$this->name} = {$this->value->to_php()};";
    }
}

abstract class Member
{
    private $name;
    private $static         = false;
    private $access         = 'public';
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    public function get_name() { return $this->name; }
    
    public function is_static() { return $this->static; }
    public function set_static($static) { $this->static = (bool) $static; }
    
    public function get_access() { return $this->access; }
    public function set_access($access) { $this->access = $access; }
    public function set_access_public() { $this->set_access('public'); }
    public function set_access_protected() { $this->set_access('protected'); }
    public function set_access_private() { $this->set_access('private'); }
    
    protected function preamble() {
        $php = '';
        if ($this->is_static()) $php .= 'static ';
        $php .= $this->access;
        return $php;
    }
}

class Variable extends Member
{
    private $value_present  = false;
    private $value          = null;
    
    public function set_value($value) {
        $this->value = value_or_object($value);
        $this->value_present = true;
    }
    
    public function remove_value($value) {
        $this->value = null;
        $this->value_present = false;
    }
    
    public function to_php() {
        $php = $this->preamble();
        $php .= " \${$this->get_name()}";
        if ($this->value_present) {
            $php .= " = {$this->value->to_php()}";
        }
        $php .= ";";
        return $php;
    }
}


class Method extends Member
{
    /**
     * Creates a Method which, when called, will delegate to a given instance variable
     */
    public static function create_delegate_for_reflection(\ReflectionMethod $method, $variable) {
        
        $method_name = $method->getName();
        
        $m = new Method($method_name);
        $m->set_arg_list(ArgumentList::create_from_reflection_method($method));
        
        if ($method->returnsReference()) {
            $m->set_reference_returned(true);
        }
        
        if ($method->isPublic()) {
            $m->set_access_public();
        } elseif ($method->isProtected()) {
            $m->set_access_protected();
        } elseif ($method->isPrivate()) {
            $m->set_access_private();
        }
        
        $body  = '
            return call_user_func_array(
                array($this->' . $variable . ', "' . $method_name . '"),
                func_get_args()
            );
        ';
        
        $m->set_body(trim(preg_replace('/\s+/', ' ', $body)));
        
        return $m;
        
    }
    
    private $final              = false;
    private $abstract           = false;
    private $reference_returned = false;
    
    private $arg_list           = '';
    private $body               = '';
    
    private $annotation         = null;
    
    public function is_final() { return $this->final; }
    public function set_final($f) { $this->final = (bool) $b; }
    
    public function is_abstract() { return $this->abstract; }
    public function set_abstract($a) { $this->abstract = (bool) $a; }
    
    public function is_reference_returned() { return $this->reference_returned; }
    public function set_reference_returned($r) { $this->reference_returned = (bool) $r; }
    
    public function get_arg_list() { return $this->arg_list; } 
    public function set_arg_list($args) { $this->arg_list = literal_or_object($args); }
    
    //
    // Annotations
    
    public function has_annotation() { return $this->annotation !== null; }
    public function get_annotation() { return $this->annotation; }
    public function set_annotation($annote) { $this->annotation = $annote; }
    
    public function arity() {
        if ($this->arg_list instanceof ArgumentList) {
            return $this->arg_list->arity();
        } else {
            return null;
        }
    }

    public function get_body() { return $this->body; }
    public function set_body($body) { $this->body = $body; }
    
    public function to_php() {
        $php = $this->preamble();
        if ($this->is_final()) $php .= " final";
        if ($this->is_abstract()) $php .= " abstract";
        $php .= " function ";
        if ($this->is_reference_returned()) $php .= "&";
        $php .= "{$this->get_name()}({$this->arg_list->to_php()})";
        if ($this->is_abstract()) {
            $php .= ";";
        } else {
            $php .= " {\n{$this->body}\n}";
        }
        return $php;
    }
    
    public function before($body) {
        $this->body = $body . $this->body;
    }
    
    public function around($body) {
        $this->body = str_replace('{{BODY}}', $this->body, $body);
    }
    
    public function after($body) {
        $this->body .= $body;
    }
}
?>