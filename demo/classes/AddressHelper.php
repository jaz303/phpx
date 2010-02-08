<?php
class AddressHelper {
    public function is_domestic() {
        return $this->get_country() == 'UK';
    }
}
?>