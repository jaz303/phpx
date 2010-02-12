<?php
namespace phpx;

class SourceFile
{
    private $bits = array();
    
    public function push($thing) {
        $this->bits[] = $thing;
    }
    
    public function get_defined_classes() {
        $out = array();
        foreach ($this->bits as $bit) {
            if ($bit instanceof ClassDef) $out[] = $bit;
        }
        return $out;
    }
    
    public function finalise_classes() {
        foreach ($this->get_defined_classes() as $class_def) {
            $class_def->finalise();
        }
    }
    
    public function to_php() {
        return implode('', array_map(function($b) {
            return is_object($b) ? $b->to_php() : $b;
        }, $this->bits));
    }
}

class ClassDef
{
    private $name;
    
    private $namespace          = '';
    
    private $final              = false;
    private $abstract           = false;
    private $superclass         = null;
    private $interfaces         = array();
    
    private $access             = null;
    
    private $patterns           = array();
    private $static_patterns    = array();
    
    private $chunks             = array();
    
    private $annotation         = null;
    
    public function __construct($name) {
        $this->name = $name;
        $this->access = new AccessStack;
        $this->access->push(new Access('public'));
    }
    
    public function get_name() { return $this->name; }
    
    public function has_namespace() { return strlen($this->namespace) > 0; }
    public function get_namespace() { return $this->namespace; }
    public function set_namespace($ns) { $this->namespace = $ns; }
    
    public function get_qualified_name() {
        $qn = '\\';
        if ($this->has_namespace()) {
            $qn .= $this->get_namespace() . '\\';
        }
        $qn .= $this->name;
        return $qn;
    }
    
    public function is_final() { return $this->final; }
    public function set_final($f) { $this->final = (bool) $f; }
    
    public function is_abstract() { return $this->abstract; }
    public function set_abstract($a) { $this->abstract = (bool) $a; }
    
    public function get_superclass() { return $this->superclass; }
    public function extend($class) { $this->superclass = $class; }
    
    public function get_interfaces() { return $this->interfaces; }
    public function implement($interface) {
        if (!in_array($interface, $this->interfaces)) {
            $this->interfaces[] = $interface;
        }
    }
    
    //
    // Annotations
    
    public function has_annotation() { return $this->annotation !== null; }
    public function get_annotation() { return $this->annotation; }
    public function set_annotation($annote) { $this->annotation = $annote; }
    
    //
    //
    
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
        
        if ($this->is_final()) {
            $php .= 'final ';
        }
        
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
        
        if ($this->has_annotation()) {
            $php .= Annotation::export_class_annotation($this) . "\n";
        }
        
        foreach ($this->methods() as $method) {
            if ($method->has_annotation()) {
                $php .= Annotation::export_method_annotation($this, $method) . "\n";
            }
        }
        
        foreach ($this->variables() as $variable) {
            if ($variable->has_annotation()) {
                $php .= Annotation::export_variable_annotation($this, $variable) . "\n";
            }
        }
        
        return $php;
        
    }
    
    //
    // 
    
    public function finalise() {
        $this->write_pattern_matching_handler();
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
    
    protected function index_to_array($index) {
        $out = array();
        foreach ($this->{$index} as $ix) {
            $out[] = $this->chunks[$ix];
        }
        return $out;
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
    // Mixins
    
    public function mixin($class) {
        $this->mixin_methods_and_variables($class, false);
        foreach (Library::lookup($class)->get_interfaces() as $interface) {
            $this->implement($interface);
        }
    }
    
    public function mixin_static($class) {
        $this->mixin_methods_and_variables($class, true);
    }
    
    public function mixin_constants($class) {
        foreach (Library::lookup($class)->constants() as $constant) {
            $this->add_constant($constant);
        }
    }
    
    protected function mixin_methods_and_variables($class, $static) {
        $def = Library::lookup($class);
        foreach ($def->select(array('method', 'variable')) as $thing) {
            if ($thing instanceof Method) {
                $this->add_method($thing);
            } elseif ($thing instanceof Variable) {
                $this->add_variable($thing);
            }
        }
    }
    
    //
    // Selection
    
    public function select($selected_things) {
        
        $method     = false;
        $variable   = false;
        $constant   = false;
        
        foreach ((array) $selected_things as $thing) $$thing = true;
        
        $out = array();
        foreach ($this->chunks as $chunk) {
            if (($method && $chunk instanceof Method) ||
                ($variable && $chunk instanceof Variable) ||
                ($constant && $chunk instanceof Constant)) {
                $out[] = $chunk;
            }
        }
        
        return $out;
        
    }
    
    public function constants() { return $this->select('constant'); }
    public function variables() { return $this->select('variable'); }
    public function methods() { return $this->select('method'); }
    
    //
    // Magic Mojo
    
    public function __call($method, $args) {
        
        // Existence check
        if (preg_match('/^(constant|variable|method)_defined$/', $method, $match)) {
            return $this->index_contains($match[0], $args[0]);
        }
        
        // Collections
        if (preg_match('/^(constant|variable|method)s$/', $method, $match)) {
            return $this->index_to_array($match[0]);
        }
        
        //
        // Index manipulation
        
        $words = explode('_', $method);
        if (count($words) >= 2) {
            $verb = array_shift($words);
            $type = array_pop($words);
            
            if (in_array($type, array('constant', 'variable', 'method'))) {
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
                }
            }
        }
        
        //
        // Delegate anything else to the macro system
        
        Macro::apply($method, $this, $args);
        
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
    
    //
    // Pattern matching
    
    public function add_pattern($pattern, $args, $body = null) {
        if (!is_object($pattern)) $pattern = new Value($pattern);
        $pattern = $pattern->to_php();
        $native_method_name = '_' . md5('i:' . $pattern);
        $this->define_protected_instance_method($native_method_name, $args, $body);
        $this->patterns[$pattern] = $native_method_name;
    }
    
    public function add_static_pattern($pattern, $args, $body = null) {
        if (!is_object($pattern)) $pattern = new Value($pattern);
        $pattern = $pattern->to_php();
        $native_method_name = '_' . md5('s:' . $pattern);
        $this->define_protected_static_method($native_method_name, $args, $body);
        $this->static_patterns[$pattern] = $native_method_name;
    }
    
    private function write_pattern_matching_handler() {
        if (count($this->patterns)) {
            $this->define_public_instance_method(
                '__call',
                '$method, $args', 
                $this->body_for_pattern_matcher($this->patterns, '$this')
            );
        }
        if (count($this->static_patterns)) {
            $this->define_public_static_method(
                '__callStatic',
                '$method, $args', 
                $this->body_for_pattern_matcher($this->static_patterns, 'get_called_class()')
            );
        }
    }
    
    private function body_for_pattern_matcher($patterns, $context) {
        $body = '';
        foreach ($patterns as $regex => $native_method) {
            $regex = new Literal($regex);
            $body .= 'if (preg_match(' . $regex->to_php() . ', $method, $matches)) {';
            $body .= '  $args[] = $matches;';
            $body .= '  return call_user_func_array(array(' . $context . ', "' . $native_method . '"), $args);';
            $body .= '}';
        }
        $body .= 'throw new \\Exception("Unknown method \'$method\'");';
        return $body;
    }

    //
    // Macros
    
    public function attr_reader() {
        foreach (func_get_args() as $arg) {
            if (!$this->variable_defined($arg)) {
                $this->define_protected_instance_variable($arg);
            }
            $this->define_public_instance_method("get_{$arg}", '
                return $this->' . $arg . ';
            ');
        }
    }
    
    public function attr_writer() {
        foreach (func_get_args() as $arg) {
            if (!$this->variable_defined($arg)) {
                $this->define_protected_instance_variable($arg);
            }
            $this->define_public_instance_method("set_{$arg}", '$v', '
                $this->' . $arg . ' = $v;
            ');
        }
    }
    
    public function attr_accessor() {
        call_user_func_array(array($this, 'attr_reader'), func_get_args());
        call_user_func_array(array($this, 'attr_writer'), func_get_args());
    }
}
?>