<?php

class DmObject {
    
    public $tableName;
    public $columnNames;
    public $autoColumns;
    
    function __construct ($tableName,$columnNames,$autoColumns = null) {
        
        $this->tableName = $tableName;
        $this->columnNames = $columnNames;
        $this->autoColumns = $autoColumns;
    }
    
    function citeria ($crit, $order=null) {
        $query = "select * from $this->tableName where $crit";
        if ($order != null) {
            $query .= " order by $order";
        }
        return Database::queryAsArray($query);
    }
    
    function getAll () {
        
        $query = "select ".DmObject::buildList($this->columnNames)." from ".$this->tableName;
        return Database::queryAsArray($query);
    }
    
    function getByColumnName ($name,$val) {
        $name = Database::escape($name);
        $val = Database::escape($val);
        return $this->citeria("'$name' = '$val'");
    }
            
    function create ($namesValues) {
        
        // build insert query
        $insertQuery = "insert into $this->tableName ";
        $insertQuery .= "(".DmObject::buildList(array_keys($namesValues),false).") ";
        $insertQuery .= "values (".DmObject::buildList(array_values($namesValues)).") ";
        // run the query return result
        // echo $insertQuery;
        Database::query($insertQuery);
        $obj = Database::queryAsObject("select max(id) as max from ".$this->tableName);
        return $obj->max;
    }
    
    function update ($matchNamesValues,$updateNamesValues) {
        // build update query
        $updateQuery = "update '$tableName' set ";
        $updateQuery .= DmObject::buildParams($updateNamesValues, "=", ",");
        $updateQuery .= "where ".DmObject::buildParams($matchNamesValues,"=","and");

        // run the query return result
        return Database::query($updateQuery);
    }
    
    function save ($matchNamesValues,$updateNamesValues) {
        
        // run the update query if no rows affected then insert
        $result = Database::query($updateQuery);
        if (Database::affectedRows($obj->update($matchNamesValues,$updateNamesValues)) == 0) {
            return $obj->create($updateNamesValues);
        }
    }
    
    function delete ($matchNamesValues) {
        
        // build delete query and return result
        $deleteQuery = "delete from '$tableName' where ";
        $deleteQuery .= DmObject::buildParams($matchNamesValues, "=", "and");
        return Database::query($updateQuery);
    }
    
    function getByColumn ($columnName,$val) {
        $columnName = Database::escape($columnName);
        $val = Database::escape($val);
        return $this->citeria("$columnName = '$val'");
    }
        
    function search ($namesValues) {
        return DataModel::searchPhysicalTable($tableName, $namesValues);
    }
    
    static function buildParams ($namesValues,$operator,$seperator) {
        $params = "";
        $first = true;
        foreach ($namesValues as $key => $value) {
            if (!$fist) {
                $updateQuery .= " $seperator ";
            }
            $first = false;
            $name = Database::escape($name);
            $value = Database::escape($value);
            $params .= "'$name' $operator '$value' ";
        }
        return $params;
    }
    
    static function buildList ($namesArray,$escape=true) {
        $list = "";
        $first = true;
        foreach ($namesArray as $name) {
            if (!$first) {
                $list .= ",";
            }
            $first = false;
            $name = Database::escape($name);
            $list .= $escape ? " '$name'" : " $name";
        }
        return $list;
    }
    
}

?>