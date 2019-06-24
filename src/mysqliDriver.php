<?php

class MysqliDriver extends DatabaseDriver {
    
    private $connected = false;
    private $mysqliLink = null;
    
    function connect ($hostname, $username, $password, $schema) {
        $this->mysqliLink = mysqli_connect($hostname,$username,$password);
        if (false !== $this->mysqliLink && true === mysqli_select_db($this->mysqliLink, $schema)) {
            $this->connected = true;
        } else {
            throw new Exception("MysqliDriver::connect() : failed to connect : ".$this->getError());
        }
        return $this->connected;
    }
    
    function isConnected () {
        return $this->connected;
    }
    
    function getTableNames () {
        $tableNames = array();
        $result = mysqli_query($this->mysqliLink, "select table_name from information_schema.tables where table_schema = database()");
        foreach ($this->fetchAllRows($result, false) as $row) {
            $tableNames []= $row[0];
        }
        return $tableNames;
    }
    
    function getTableFeilds ($tableName) {
        $tableName = $this->escape($tableName);
        $result = mysqli_query($this->mysqliLink, "SELECT column_name, data_type, column_default, is_nullable, numeric_precision, character_maximum_length, extra from information_schema.columns WHERE table_schema=database() and table_name='$tableName'");
        return $this->fetchAllRows($result, true);
    }
    
    function getLastInsertId ($tableName) {
        $result = mysqli_query($this->mysqliLink, "select max(id) as id from ".$tableName);
        $row = $this->fetchRow($result);
        return $row["id"];
    }
    
    function query ($query) {
        $result = mysqli_query($this->mysqliLink, $query);
        if ($result === false) {
            $this->error = true;
            throw new Exception("MysqliDriver::query() : query failed : ".$this->getError());
        }
        return $result;
    }
    
    function escape ($input) {
        return mysqli_real_escape_string($this->mysqliLink, $input);
    }
    
    function affectedRows () {
        return mysqli_affected_rows($this->mysqliLink);
    }
    
    function numRows ($result) {
        return mysqli_num_rows($result);
    }
    
    function fetchRow ($result, $assoc=true) {
        return mysqli_fetch_array($result, $assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
    }
    
    function fetchAllRows ($result, $assoc=true) {
        return mysqli_fetch_all($result, $assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
    }
    
    function getError () {
        $error = mysqli_error($this->mysqliLink);
        if (empty($error)) {
            return false;
        }
        return $error;
    }
    
    function close () {
        mysqli_close($this->mysqliLink);
        $this->connected = false;
    }
}

?>