<?php
namespace phpx;

class Access
{
    /**
     * Create an access object from either a Reflector, Access or string
     *
     * strings should be of the format: "public abstract final"
     */
    public static function coerce($thing) {
        if ($thing instanceof \Reflector) {
            $access = self::for_reflection($thing);
        } elseif ($thing instanceof Access) {
            $access = $thing;
        } elseif (is_string($thing)) {
            $access = new Access;
            self::parse_string_into_access($access, $thing);
        }
        return $access;
    }
    
    public static function parse_string_into_access($access, $string) {
        foreach (preg_split('/\s+/', strtolower(trim($string))) as $chunk) {
            if (method_exists($access, "set_{$chunk}")) {
                $access->{"set_{$chunk}"}(true);
            }
        }
    }
    
    public static function for_reflection($ref) {
        
        $access = new Access;
        
        // properties and methods
        
        $access->set_static($ref->isStatic());
        
        if ($ref->isPublic()) {
            $access->set_public();
        } elseif ($ref->isProtected()) {
            $access->set_protected();
        } elseif ($ref->isPrivate()) {
            $access->set_private();
        }
        
        // methods only
        
        if ($ref instanceof \ReflectionMethod) {
            $access->set_abstract($ref->isAbstract());
            $access->set_final($ref->isFinal());
        }
        
        return $access;
    
    }
    
    public static function merge() {
        $stack = new AccessStack;
        foreach (func_get_args() as $arg) {
            if (!is_array($arg)) $arg = array($arg);
            foreach ($arg as $accessible) {
                $stack->push(self::coerce($accessible));
            }
        }
        return $stack->effective_access();
    }
    
    private $access         = null;
    private $final          = null;
    private $abstract       = null;
    private $static         = null;
    
    public function __construct($string = null) {
        if ($string !== null) {
            self::parse_string_into_access($this, $string);
        }
    }
    
    public function has_access() { return $this->access !== null; }
    public function get_access() { return $this->access; }
    public function set_access($a) { $this->access = $a; }
    public function clear_access() { $this->access = null; }
    
    public function is_public() { return $this->access == 'public'; }
    public function is_protected() { return $this->access == 'protected'; }
    public function is_private() { return $this->access = 'private'; }
    
    public function set_public() { $this->access = 'public'; }
    public function set_protected() { $this->access = 'protected'; }
    public function set_private() { $this->access = 'private'; }
    
    public function has_final() { return $this->final !== null; }
    public function is_final() { return $this->final; }
    public function set_final($v) { $this->final = (bool) $v; }
    public function clear_final() { $this->final = null; }
    
    public function has_abstract() { return $this->abstract !== null; }
    public function is_abstract() { return $this->abstract; }
    public function set_abstract($v) { $this->abstract = (bool) $v; }
    public function clear_abstract() { $this->abstract = null; }
    
    public function has_static() { return $this->static !== null; }
    public function is_static() { return $this->static; }
    public function set_static($v) { $this->static = (bool) $v; }
    public function clear_static() { $this->static = null; }
    
    public function set_instance($v) { $this->set_static(!$v); }
    
    public function to_php() {
        $chunks = array();
        if ($this->has_access())    $chunks[] = $this->get_access();
        if ($this->is_final())      $chunks[] = 'final';
        if ($this->is_abstract())   $chunks[] = 'abstract';
        if ($this->is_static())     $chunks[] = 'static';
        return implode(' ', $chunks);
    }
}

class AccessStack
{
    private $access = array();
    
    public function push($thing) { $this->access[] = Access::coerce($thing); }
    public function pop() { return array_pop($this->access); }
    
    public function effective_access() {
        $access = new Access;
        for ($i = count($this->access) - 1; $i >= 0; $i--) {
            $curr = $this->access[$i];
            if (!$access->has_access() && $curr->has_access())
                $access->set_access($curr->get_access());
            if (!$access->has_final() && $curr->has_final())
                $access->set_final($curr->is_final());
            if (!$access->has_abstract() && $curr->has_abstract())
                $access->set_abstract($curr->is_abstract());
            if (!$access->has_static() && $curr->has_static())
                $access->set_static($curr->is_static());
        }
        return $access;
    }
}
?>