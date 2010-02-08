<?php
// Static mixin with some toy SQL generation functionality
// Classes mixing in SimpleORM should be annotated with :table_name key
class SimpleORM
{
    public static function sql_for_find_all() {
        return "SELECT * FROM " . self::table_name();
    }
    
    public static function sql_for_find_by_id($id) {
        return "SELECT * FROM " . self::table_name() . " WHERE id = " . (int) $id;
    }
    
    public static function table_name() {
        // get_called_class() will return the name of the class into which we are mixed
        $annotes = \phpx\annotations_for(get_called_class());
        return $annotes['table_name'];
    }
}
?>