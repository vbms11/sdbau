<?php

class MessageTable extends AbstractTable {
    
    var $id;
    var $userid;
    var $message;
    
    static function getTableName() {
        return "message";
    }
    
    static function getColumns() {
        return array(
            "id" => array(
                parent::type => parent::type_number,
                parent::autoIncrement => true,
            ),
            "userid" => array(
                parent::type => parent::type_number,
                parent::length => 20
            ),
            "message" => array(
                parent::type => parent::type_text,
                parent::nullable => true
            )
        );
    }
    
}
