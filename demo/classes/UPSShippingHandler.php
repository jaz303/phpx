<?php
class UPSShippingHandler implements ShippingHandler {
    public function ship() {
        echo "Shipping via UPS!\n";
    }
}
?>