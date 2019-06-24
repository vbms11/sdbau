<?php 

abstract class AbstractTable {
    
    // reference to the database driver
    var $DB;
    
    const type = 1;
    const nullable = 2;
    const length = 3;
    const default = 4;
    const validator = 5;
    const translatable = 6;
    const autoIncrement = 7;
    
    const type_number = 1;
    const type_decimal = 2;
    const type_string = 3;
    const type_text = 4;
    const type_boolean = 5;
    const type_date = 6;
    const type_timestamp = 7;
    
    const validator_number = 1;
    const validator_email = 2;
    const validator_expression = 3;
    
    const default_numberLength = 20;
    const default_stringLength = 200;
    
    abstract static function getTableName ();
    
    abstract static function getColumns ();
    
    function __construct() {
    }
    
    function __call ($name, $arguments) {
        if (strpos("by", $name)) {
            $name = strtolower(substr($name, 2));
            if (in_array($name, array_keys($this->getColumns()))) {
                return $this->byColumnValue($name, $arguments[0]);
            }
        }
    }
    
    function byColumnValue ($name, $value) {
        $value = $this->DB->escape($value);
        $result = $this->DB->queryAsRow("select ".$this->getFeildsQueryPart()." from ".$this->theTableName()." where ".$name." = '".$id."'", false);
        foreach (array_keys($this->getFeilds()) as $columnPos => $columnName) {
            $this->$columnName = $result[$columnPos];
        }
        return $this;
    }
    
    static function getAll ($limmit=null, $start=null) {
        $results = $this->DB->queryAsRowArray("select * from ".$this->theTableName()." ", false);
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results);
    }
    
    /*
     * deletes 
     */
    function delete () {
        $this->DB->query("delete from table ".($this->theTableName())." where ".self::feild_id."yx = '".$this->DB->escape($this->id)."'");
    }
    
    /*
     * updates or inserts
     */
    function save () {
        $idFeild = $this->getIdFeild();
        if ($idFeild !== null) {
            if ($this->$idFeild === null) {
                $feildNames = array();
                $feildValues = array();
                foreach (array_keys($this->getColumns()) as $columnName) {
                    $feildNames []= $columnName;
                    $feildValues []= !isset($this->$columnName) || $this->$columnName === null ? "null" : "'".$this->DB->escape($this->$columnName)."'";
                }
                $query = "insert into ".$this->theTableName()." ";
                $queryNames = "(".implode(",", $feildNames).")";
                $queryValues = "values(".implode(",", $feildValues).")";
                $query .= $queryNames." ".$queryValues;
                $this->DB->query($query);
                $this->$idFeild = $this->DB->getLastInsertId($this->theTableName());
            } else {
                $query = "update ".$this->theTableName()." set ";
                $feilds = array();
                foreach (array_keys($this->getColumns()) as $columnName) {
                    $feilds []= $columnName."=".isset($this->$columnName) || $this->$columnName === null ? "null" : "'".$this->DB->escape($this->$columnName)."'";
                }
                $query .= implode(", ", $feilds)." where ".$idFeild." = '".$this->DB->escape($this->$idFeild)."'";
                $this->DB->query($query);
            }
        } else {
            // has forign keys
            // insert if dosent already exist
        }
        
    }
    
    /*
     * returns results where all feilds match
     */
    function find ($nameValues, $order=null, $ascending=true) {
        $query = "select ".$this->getFeildsQueryPart()." from ".$this->theTableName()." where ";
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = '".$this->DB->escape($value)."' ";
            }
        }
        $query .= implode(" and ", $conditions)." ";
        if ($order !== null && in_array($order, $columnNames)) {
            $query .= " order by ".$order." ".($ascending ? "asc" : "desc")." ";
        }
        $results = $this->DB->queryAsRowArray($query, false);
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results);
    }
    
    /*
     * returns results where any feilds match
     */
    function search ($nameValues) {
        $query = "select ".$this->getFeildsQueryPart()." from ".$this->theTableName()." where ";
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = '".$this->DB->escape($value)."' ";
            }
        }
        $query .= implode(" or ", $conditions)." ";
        if ($order !== null && in_array($order, $columnNames)) {
            $query .= " order by ".$order." ".($ascending ? "asc" : "desc")." ";
        }
        $results = $this->DB->queryAsRowArray($query, false);
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results);
    }
    
    function like ($nameValues) {
        $query = "select ".$this->getFeildsQueryPart()." from ".$this->theTableName()." where ";
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = like '%".$this->DB->escape($value)."%' ";
            }
        }
        $query .= implode(" or ", $conditions)." ";
        if ($order !== null && in_array($order, $columnNames)) {
            $query .= " order by ".$order." ".($ascending ? "asc" : "desc")." ";
        }
        $results = $this->DB->queryAsRowArray($query, false);
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results);
    }
    
    /**
     * 
     * @return array
     */
    function serialize () {
        $values = array();
        foreach ($this->getColumns() as $column) {
            $values[$column["name"]] = $this->$column["name"];
        }
        return $values;
    }
    
    function deserialize ($data) {
        foreach ($data as $value => $key) {
            $this->$key = $value[$key];
        }
    }
    
    function create () {
        $feilds = array();
        $primaryKey = null;
        foreach ($this->getColumns() as $columnName => $attributes) {
            $feild = "`".$columnName."` ";
            switch ($attributes[self::type]) {
                case self::type_number:
                    $feild .= "int(".(isset($attributes[self::length]) ? $attributes[self::length] : self::default_numberLength).") ";
                    break;
                case self::type_decimal:
                    $feild .= "double ";
                    break;
                case self::type_string:
                    $feild .= "varchar(".(isset($attributes[self::length]) ? $attributes[self::length] : self::default_stringLength).") ";
                    break;
                case self::type_text:
                    $feild .= "blob ";
                    break;
                case self::type_boolean:
                    $feild .= "boolean ";
                    break;
                case self::type_date:
                    $feild .= "date ";
                    break;
                case self::type_timestamp:
                    $feild .= "timestamp ";
                    break;
                default:
                    throw new Exception("unknown type in: ".$columnName." attributes: ".serialize($attributes));
            }
            if (isset($attributes[self::nullable]) && $attributes[self::nullable]) {
                $feild .= "null ";
            } else {
                $feild .= "not null ";
            }
            if (isset($attributes[self::default])) {
                if (is_array($attributes[self::default])) {
                    $feild .= "default ".$attributes[self::default][0];
                } else {
                    $feild .= "default '".$attributes[self::default]."'";
                }
            }
            if (isset($attributes[self::autoIncrement]) && $attributes[self::autoIncrement]) {
                $feild .= "AUTO_INCREMENT ";
                $primaryKey = ", PRIMARY KEY (`".$columnName."`) ";
            }
            $feilds []= $feild;
        }
        $query = "create table `".$this->theTableName()."` ( ";
        $query .= implode(", ", $feilds);
        if ($primaryKey != null) {
            $query .= $primaryKey;
        }
        $query .= ")";
        $this->DB->query($query);
    }
    
    function drop () {
        return $this->DB->query("drop table ".($this->getTableName()));
    }
    
    function  getInstances ($results) {
        $ret = array();
        $feilds = $this->getFeildsArray();
        foreach ($results as $result) {
            $ret []= new $this();
            foreach ($feilds as $columnPos => $columnName) {
                $this->$columnName = $result[$columnPos];
            }
        }
        return $ret;
    }
    
    function getFeildsQueryPart () {
        $feilds = array();
        foreach (array_keys($this->getColumns()) as $name) {
            $feilds []= $name." as ".$name;
        }
        implode(", ", $feilds);
    }
    
    function getFeildsArray () {
        $feilds = array();
        foreach (array_keys($this->getColumns()) as $name) {
            $feilds []= $name;
        }
        return $feilds;
    }
    
    function getQueryBuilder () {
        return new QueryBuilder($this->DB, $this->theTableName());
    }
    
    function theTableName () {
        return $this->DB->tablePrefix.$this->getTableName();
    }
    
    function getIdFeild () {
        foreach ($this->getColumns() as $name => $attributes) {
            if (isset($attributes[self::autoIncrement])) {
                return $name;
            }
        }
    }
}

?>