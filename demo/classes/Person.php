<?php
class Person
{
    include \Addressable;
    include const \Addressable;
    
    eval {
        foreach (array('title', 'forename', 'surname') as $property) {
            $class->attr_accessor($property);
        }
    }
}
?>