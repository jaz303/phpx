<?php
class Controller
{
    protected $action;
    protected $params;
    
    include \FilterMixin;
    include \RescueMixin;
    
    eval {
        $class->rescue_from('\\RecordNotFoundException', 'rescue_not_found');
    }
    
    public function invoke_action($action, array $params) {
        $this->action = $action;
        $this->params = $params;
        $this->perform_with_rescue('perform_invoke');
    }
    
    protected function perform_invoke() {
        $this->run_filter_chain('before');
        $this->{"_{$this->action}"}();
        $this->run_filter_chain('after');
    }
    
    protected function rescue_not_found($exception) {
        echo "User not found!<br />";
    }
}
?>