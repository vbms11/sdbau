<?php

class QueryBuilder {
    
    var $DB;
    
    var $feilds;
    var $table;
    var $alias;
    var $joins = array();
    var $condition = null;
    var $limmit = null;
    var $offset = null;
    var $order = null;
    
    function __construct(&$DB, $tableName, $tableAlias=null) {
        $this->DB = &$DB;
        $this->setTable($tableName, $tableAlias);
    }
    
    function setTable ($tableName, $tableAlias=null) {
        $this->table = $tableName;
        if ($tableAlias == null) {
            $tableAlias = $tableName;
        } else {
            $this->alias = $tableAlias;
        }
        return $this;
    }
    
    function setCondition ($condition) {
        $this->condition = $condition;
        return $this;
    }
    
    function setOrder ($order) {
        $this->order = $order;
    }
    
    function setFeilds ($feilds) {
        $this->feilds = $feilds;
        return $this;
    }
    
    function getFeilds () {
        if (count($this->joins) == 0) {
            return implode(", ", array_keys($this->table->getColumns()));
        }
        return $this->feilds;
    }
    
    function addJoin ($joinType, $tableName, $tableAlias, $condition) {
        $this->joins []= array($joinType, $tableName, $tableAlias, $condition);
        return $this;
    }
    
    function setLimmit ($limmit, $start=null) {
        $this->limmit = $limmit;
        if ($start != null) {
            $this->start = $start;
        }
        return $this;
    }
    
    function getQuery () {
        $query = "";
        foreach ($this->joins as $join) {
            $query .= $join[0]." ".$join[1]." as ".$join[2]." on ".$join[3]." ";
        }
        if ($this->condition != null) {
            $query .= "where ".$this->condition." ";
        }
        if ($this->order != null) {
            $query .= "order by ".$this->order;
        }
        if ($this->limmit != null) {
            $query .= "limmit ".$this->limmit." ";
        }
        if ($this->offset != null) {
            $query .= "offset ".$this->offset." ";
        }
        $query = "select ".$this->getFeilds()." from ".$this->table." ".$this->alias." ".$query;
        return new Query($this->DB, $query);
    }
}