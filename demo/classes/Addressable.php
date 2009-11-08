<?php
abstract class Addressable implements Countable
{
    const UK        = 'UK';
    const US        = 'America';
    
    protected $sender implements Recipient;
    
    eval {
        $class->attr_accessor('sender');
        
        foreach (array('address_1', 'address_2', 'city', 'postcode', 'country') as $part) {
            $class->attr_accessor($part);
        }
    }
}
?>