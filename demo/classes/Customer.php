<?php
/**
 * :table_name = "customers"
 */
class Customer {
    include static SimpleORM;
    
    eval {
        $class->addressable();
        $class->attr_accessor('status');
    }
    
    public function set_status($s) {
        $this->status = trim(strtoupper($s));
    }
    
    public function "/^is_(active|disabled|banned)$/"($matches) {
        return $this->get_status() == strtoupper($matches[1]);
    }
    
    private $shipper implements \ShippingHandler;
    
    public function __construct() {
        $this->shipper = new \UPSShippingHandler;
    }
}
?>