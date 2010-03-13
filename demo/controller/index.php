<?php
require '../../boot.php';

set_include_path(dirname(__FILE__));

class FilterMacro
{
    public function add_filter($class, $chain, $where, $method, $options = array()) {
        $options['method'] = $method;
        if ($where == 'start') {
            $class->inheritable_array_unshift("filters__$chain", $options);
        } elseif ($where == 'end') {
            $class->inheritable_array_push("filters__$chain", $options);
        }
    }
    
    public function before_filter($class, $method, $options = array()) {
        $this->add_filter($class, 'before', 'end', $method, $options);
    }
    
    public function after_filter($class, $method, $options = array()) {
        $this->add_filter($class, 'after', 'end', $method, $options);
    }
}

class RescueMacro
{
    public function rescue_from($class, $exception_class, $rescue_method) {
        $class->merge_inheritable_array('rescue_macro__rescues', array($exception_class => $rescue_method));
    }
}

class RecordNotFoundException extends Exception
{
}

phpx\PHPX::init();
phpx\Macro::register('\\FilterMacro');
phpx\Macro::register('\\RescueMacro');

$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$params = $_GET;

$controller = new UsersController;
$controller->invoke_action($action, $params);
?>