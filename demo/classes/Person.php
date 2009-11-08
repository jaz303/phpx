<?php
abstract class Person
{
    include \Addressable;
    include const \Addressable;
    
    eval {
        foreach (array('title', 'forename', 'surname') as $property) {
            $class->attr_accessor($property);
        }
    }
    
    public function "/^render_(person|item|thing)$/"($number, $matches) {
        echo "$number of {$matches[1]}\n\n";
    }
}
?>