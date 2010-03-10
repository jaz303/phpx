<?php
namespace phpx;

class InheritableAttributes
{
    public static function finalize($class) {
        
        $hierarchy = $class->get_class_hierarchy();
        if ($hierarchy === null) {
            return;
        }
        
        $vals = array();
        foreach ($hierarchy as $class) {
            if (isset($class->stash['inheritable_attributes']['attrib'])) {
                foreach ($class->stash['inheritable_attributes']['attrib'] as $k => $v) {
                    $vals[$k] = $v;
                }
            }
            if (isset($class->stash['inheritable_attributes']['array'])) {
                foreach ($class->stash['inheritable_attributes']['array'] as $k => $v) {
                    if (!isset($vals[$k])) $vals[$k] = array();
                    $vals[$k] = array_merge($vals[$k], $v);
                }
            }
        }
        
        foreach ($vals as $k => $v) {
            $class->define_protected_static_variable($k, $v);
        }
    
    }
    
    public function write_inheritable_attribute($class, $name, $value) {
        $class->stash['inheritable_attributes']['attrib'][$name] = $value;
    }
    
    public function merge_inheritable_array($class, $name, $array) {
        if (isset($class->stash['inheritable_attributes'][$name])) {
            $class->stash['inheritable_attributes']['array'][$name] = array_merge($class->stash['inheritable_attributes'][$name], array());
        } else {
            $class->stash['inheritable_attributes']['array'][$name] = $array;
        }
    }
}
?>