<?php
namespace phpx;

class Forward implements \IteratorAggregate
{
    private static $forwards = array();
    
    public static function before($class_name, $block = null) {
        return self::stash('before', $class_name, $block);
    }
    
    public static function after($class_name, $block = null) {
        return self::stash('after', $class_name, $block);
    }
    
    private static function stash($where, $class_name, $block) {
        $class_name = absolute_class_name($class_name);
        if ($block === null) {
            $proxy = new Forward;
            self::$forwards[$class_name][] = array($where, $proxy);
            return $proxy;
        } else {
            self::$forwards[$class_name][] = array($where, $block);
            return;
        }
    }
    
    public static function apply($where, ClassDef $class_def) {
        $qn = $class_def->get_qualified_name();
        if (isset(self::$forwards[$qn])) {
            foreach (self::$forwards[$qn] as $fwd) {
                if ($fwd[0] == $where) {
                    $thing = $fwd[1];
                    if ($thing instanceof Forward) {
                        foreach ($thing as $call) {
                            call_user_func_array(array($class_def, $call[0]), $call[1]);
                        }
                    } else {
                        $thing($class_def);
                    }
                }
            }
        }
    }
    
    private $recorded = array();
    
    public function __call($method, $args) {
        $this->recorded[] = array($method, $args);
        return $this;
    }
    
    public function getIterator() {
        return new \ArrayIterator($this->recorded);
    }
}
?>