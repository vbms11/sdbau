<?php

class DatabaseConnection {

    var $username;
    var $hostname;
    var $password;
    var $schema;
    var $driver;
    
    function __construct($hostname, $username, $password, $schema, &$driver) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->schema = $schema;
        $this->driver = &$driver;
    }
    
    function connect () {
        return $this->driver->connect($this->hostname, $this->username, $this->password, $this->schema);
    }
    
    function getTable ($theTable) {
        $theTable->DB = &$this->driver;
        return $theTable;
    }
    
    function getQueryBuilder ($tableName, $tableAlias) {
        return new QueryBuilder($this->driver, $tableName, $tableAlias);
    }
    
    function getObjectFactory () {
        return new ObjectFactory($this->driver);
    }
}