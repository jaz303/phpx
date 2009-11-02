<?php
namespace phpx;

function value_or_object($thing) {
    return is_object($thing) ? $thing : new Value($thing);
}

function literal_or_object($thing) {
    return is_object($thing) ? $thing : new Literal($thing);
}

class SourceFile
{
    private $bits = array();
    
    public function push($thing) {
        $this->bits[] = $thing;
    }
    
    public function to_php() {
        $out = '';
        foreach ($this->bits as $b) {
            $out .= is_object($b) ? $b->to_php() : $b;
        }
        return $out;
    }
}

class ClassDef
{
    private $name;
    private $abstract       = false;
    private $superclass     = null;
    private $interfaces     = array();
    
    private $access         = null;
    
    private $chunks         = array();
    
    public function __construct($name) {
        $this->name = $name;
        $this->access = new AccessStack;
        $this->access->push(new Access('public'));
    }
    
    public function get_name() { return $this->name; }
    
    public function is_abstract() { return $this->abstract; }
    public function set_abstract($a) { $this->abstract = (bool) $a; }
    
    public function extend($class) { $this->superclass = $class; }
    public function implement($interface) { $this->interfaces[] = $interface; }
    
    public function with_access($access, $lambda) { 
        try {
            $this->access->push($access);
            $retval = $lambda($this);
            $this->access->pop();
            return $retval;
        } catch (\Exception $e) {
            $this->access->pop();
            throw $e;
        }
    }
    
    public function current_access() {
        return $this->access->effective_access();
    }
    
    public function to_php() {
        
        $php = '';
        
        if ($this->is_abstract()) {
            $php .= 'abstract ';
        }
        
        $php .= "class {$this->name}";
        
        if ($this->superclass) {
            $php .= " extends {$this->superclass}";
        }
        
        if (!empty($this->interfaces)) {
            $php .= " implements " . implode(', ', $this->interfaces);
        }
        
        $php .= " {\n";
        
        foreach ($this->chunks as $chunk) {
            if ($chunk === null) continue;
            $php .= $chunk->to_php() . "\n";
        }
        
        $php .= "}\n";
        
        return $php;
        
    }
    
    //
    // Indexes
    
    private $constant   = array();
    private $variable   = array();
    private $methods    = array();
    
    protected function index_contains($index, $name) {
        return isset($this->{$index}[$name]);
    }
    
    protected function index_get($index, $name) {
        if (!$this->index_contains($index, $name)) {
            // throw
        } else {
            $offset = $this->{$index}[$name];
            return $this->chunks[$offset];
        }
    }
    
    protected function add_to_index($index, $thing) {
        $name = $thing->get_name();
        if ($this->index_contains($index, $name)) {
            $this->remove_from_index($index, $name);
        }
        $this->chunks[] = $thing;
        $this->{$index}[$name] = count($this->chunks) - 1;
    }
    
    protected function remove_from_index($index, $name) {
        if (!$this->index_contains($index, $name)) {
            // throw
        } else {
            $offset = $this->{$index}[$name];
            $this->chunks[$offset] = null;
            unset($this->{$index}[$name]);
        }
    }
    
    //
    // Magic Mojo
    
    public function __call($method, $args) {
        
        // Lookup
        if (preg_match('/^(constant|variable|method)$/', $method, $match)) {
            return $this->index_get($match[0], $args[0]);
        }
        
        // Existence check
        if (preg_match('/^(constant|variable|method)_defined$/', $method, $match)) {
            return $this->index_contains($match[0], $args[0]);
        }
        
        $words = explode('_', $method);
        
        if (count($words) < 2) {
            throw new \Exception("Expecting at least 2 args");
        }
        
        $verb = array_shift($words);
        $type = array_pop($words);
        
        if (!in_array($type, array('constant', 'variable', 'method'))) {
            throw new \Exception("Type should be constant, variable or method");
        }
        
        switch ($verb) {
            case 'define':
                $this->with_access(
                    new Access(implode(' ', $words)),
                    function($class) use ($type, $args) {
                        call_user_func_array(array($class, "define_$type"), $args);
                    }
                );
                return;
            case 'add':
                $this->add_to_index($type, $args[0]);
                return;
            case 'remove':
                return $this->index_remove($type, $args[0]);
            default:
                throw new \Exception("Unknown verb...");
        }
        
    }
    
    public function define_constant($name, $value) {
        $this->add_to_index('constant', new Constant($name, $value));
    }
    
    public function define_variable() {
        $argc = func_num_args();
        $argv = func_get_args();
        $v = new Variable($argv[0]);
        $this->assign_current_access($v);
        if ($argc > 1) {
            $v->set_value($argv[1]);
        }
        $this->add_to_index('variable', $v);
    }
    
    public function define_method($name, $args, $body = null) {
        $m = new Method($name);
        $this->assign_current_access($m);
        if ($body === null) {
            $body = $args;
            $args = new ArgumentList;
        }
        $m->set_arg_list($args);
        $m->set_body($body);
        $this->add_to_index('method', $m);
    }
    
    protected function assign_current_access(Member $thing) {
        $access = $this->current_access();
        
        $thing->set_access($access->get_access());
        if ($access->is_static()) {
            $thing->set_static(true);
        }
        
        if ($thing instanceof Method) {
            if ($access->is_abstract()) {
                $thing->set_abstract(true);
            }
            if ($access->is_final()) {
                $thing->set_final(true);
            }
        }
    }
}

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

class Member
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
    
    private $abstract       = false;
    private $arg_list       = '';
    private $body           = '';
    
    public function is_abstract() { return $this->abstract; }
    public function set_abstract($a) { $this->abstract = (bool) $a; }
    
    public function get_arg_list() { return $this->arg_list; } 
    public function set_arg_list($args) { $this->arg_list = literal_or_object($args); }
    
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
        if ($this->is_abstract()) $php .= " abstract";
        $php .= " function {$this->get_name()}({$this->arg_list->to_php()})";
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