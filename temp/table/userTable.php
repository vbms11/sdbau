<?php

class UserTable extends AbstractTable {
    
    var $id;
    var $age;
    var $username;
    var $story;
    
    static function getTableName() {
        return "user";
    }
    
    static function getColumns() {
        return array(
            "id" => array(
                parent::type => parent::type_number,
                parent::autoIncrement => true,
            ),
            "age" => array(
                parent::type => parent::type_number,
                parent::length => 10
            ),
            "username" => array(
                parent::type => parent::type_string,
                parent::length => 100
            ),
            "story" => array(
                parent::type => parent::type_text,
                parent::nullable => true
            )
        );
    }
    
}
