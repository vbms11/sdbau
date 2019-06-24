<?php

abstract class DatabaseDriver {
    
    abstract function connect ($hostname, $username, $password, $schema);
    abstract function isConnected ();
    abstract function getLastInsertId ($tableName);
    abstract function query ($query);
    abstract function escape ($input);
    abstract function affectedRows ();
    abstract function numRows ($result);
    abstract function fetchRow ($result, $assoc);
    abstract function fetchAllRows ($result, $assoc);
    abstract function getError ();
    abstract function getTableNames ();    
    abstract function getTableFeilds ($tableName);
    abstract function close ();
    
    var $tablePrefix = "";
    
    function queryAsRow ($query, $assoc=true) {
        $result = $this->query($query);
        return $this->fetchRow($query, $assoc);
    }
    
    function queryAsRowArray ($query, $assoc=true) {
        return $this->fetchAllRows($this->query($query), $assoc);
    }
}
