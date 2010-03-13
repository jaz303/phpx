<?php
class UsersController extends MyController
{
    protected $user = null;
    
    eval {
        $class->before_filter('find_user', array('only' => array('show', 'edit', 'delete')));
    }
    
    public function _index() {
        echo "<h1>Listing Users</h1>";
    }
    
    public function _show() {
        echo "<h1>Showing User</h1>";
        $this->dump_user();
    }
    
    public function _edit() {
        echo "<h1>Editing User</h1>";
        $this->dump_user();
    }
    
    public function _new() {
        echo "<h1>Create new user</h1>";
    }
    
    public function _delete() {
        echo "<h1>Delete User</h1>";
        echo "<p>Please confirm you wish to delete this user.</p>";
        $this->dump_user();
    }
    
    protected function dump_user() {
        echo "<pre>"; var_dump($this->user); echo "</pre>";
    }
    
    protected function find_user() {
        if (!isset($this->params['id'])) {
            throw new \RecordNotFoundException();
        }
        echo "<p>Finding user {$this->params['id']}</p>";
        $this->user = array(
            'id'        => (int) $this->params['id'],
            'forename'  => 'Joe',
            'surname'   => 'Bloggs'
        );
    }
}
?>