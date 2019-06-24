<?php

abstract class AbstractTranslatableTable extends AbstractTable {
    
    const translationsTableSuffix = "_translations";
    const feild_rowid = "rowid";
    const feild_language = "language";
    
    var $languageCode = null;
    
    function setLangaugeCode ($languageCode) {
        $this->languageCode = $languageCode;
    }
    
    function getLangaugeCode () {
        return $this->languageCode;
    }
    
    function getTranslationTableName () {
        return $this->theTableName().self::translationsTableSuffix;
    }
    
    function byColumnValue ($name, $value) {
        $value = $this->DB->escape($value);
        $translationTableName = $this->getTranslationTableName();
        $results =  $this->getQueryBuilder($this->DB, $this->theTableName())
            ->setFeilds($this->getFeilds())
            ->addJoin("left join", $translationTableName, $translationTableName, $translationTableName.".languagecode = '".$this->DB->escape($this->getLangaugeCode())."' and ".$translationTableName.".".self::feild_rowid." = '".$this->DB->escape($this->id))
            ->setCondition("$name = '".$value."'")
            ->getQuery()
            ->execute();
        if ($results.size == 1) {
            $this->takeValues($results->get(0));
        }
        return $this;
    }
    
    static function getAll ($limmit=null, $start=null) {
        $translationTableName = $this->getTranslationTableName();
        $results =  $this->getQueryBuilder($this->DB, $this->theTableName())
            ->setFeilds($this->getFeilds())
            ->addJoin("left join", $translationTableName, $translationTableName, $translationTableName.".languagecode = '".$this->DB->escape($this->getLangaugeCode())."'")
            ->getQuery()
            ->execute();
        if ($results.size == 0) {
            return null;
        }
        return $this->getInstances($results->asArray());
    }
    
    /*
     * deletes 
     */
    function delete () {
        $this->DB->query("delete from table ".($this->getTranslationTableName())." where ".self::feild_rowid." = '".$this->id."'");
        parent::delete();
    }
    
    /*
     * updates or inserts
     */
    function save () {
        if ($this->id === null) {
            $feildNames = array();
            $feildValues = array();
            $feildNamesTranslatable = array();
            $feildValuesTranslatable = array();
            foreach ($this->getColumns() as $columnName => $attributes) {
                if ($attributes[parent::translatable]) {
                    $feildNamesTranslatable []= $columnName;
                    $feildValuesTranslatable []= "'".$this->DB->escape($this->$columnName)."'";
                } else {
                    $feildNames []= $columnName;
                    $feildValues []= "'".$this->DB->escape($this->$columnName)."'";
                }
            }
            
            $query = "insert into ".$this->theTableName()." ";
            $queryNames .= "(".implode(",", $feildNames).")";
            $queryValues .= "values(".implode(",", $feildValues).")";
            $query .= $queryNames." ".$queryValues;
            $this->DB->query($query);
            
            $rowId = $this->DB->getLastInsertId($this->theTableName());
            $feildNamesTranslatable []= self::feild_rowid;
            $feildValuesTranslatable []= $rowId;
            $feildNamesTranslatable []= self::feild_language;
            $feildValuesTranslatable []= $this->languageCode;
            
            $queryTranslatable = "insert into ".$this->getTranslationTableName()." ";
            $feildNamesTranslatable .= "(".implode(",", $feildNamesTranslatable).")";
            $feildValuesTranslatable .= "values(".implode(",", $feildValuesTranslatable).")";
            $queryTranslatable .= $feildNamesTranslatable." ".$feildValuesTranslatable;
            $this->DB->query($queryTranslatable);
        } else {
            $feilds = array();
            $feildsTranslatable = array();
            foreach ($this->getColumns() as $columnName => $attributes) {
                if ($attributes[parent::translatable]) {
                    $feildsTranslatable []= $columnName."='".$this->DB->escape($this->$columnName)."'";
                } else {
                    $feilds []= $columnName."='".$this->DB->escape($this->$columnName)."'";
                }
            }
            $query = "update ".$this->theTableName()." set ";
            $query .= implode(", ", $feilds)." where id = '".$this->DB->escape($this->id)."'";
            $this->DB->query($query);
            
            $queryTranslatable = "update ".$this->theTableName()." set ";
            $queryTranslatable .= implode(", ", $feilds)." where ".self::feild_rowid." = '".$this->DB->escape($this->id)."' and ".self::feild_language." = '".$this->DB->escape($this->languageCode)."'";
            $this->DB->query($queryTranslatable);
            
            $this->id = $this->DB->getLastInsertId($this->theTableName());
        }
    }
    
    /*
     * returns results where all feilds match
     */
    function find ($nameValues, $order=null, $ascending=true) {
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = '".$this->DB->escape($value)."' ";
            }
        }
        $translationTableName = $this->getTranslationTableName();
        $queryBuilder =  $this->getQueryBuilder($this->DB, $this->theTableName())
            ->setFeilds($this->getFeilds())
            ->addJoin("left join", $translationTableName, $translationTableName, $translationTableName.".".self::feild_language." = '".$this->DB->escape($this->getLangaugeCode())."' and ".$translationTableName.".".self::feild_rowid." = '".$this->DB->escape($this->id))
            ->setCondition(implode(" and ", $conditions));
        if ($order !== null && in_array($order, $columnNames)) {
            $queryBuilder->setOrder($order." ".($ascending ? "asc" : "desc"));
        }
        $results = $queryBuilder->getQuery()->execute();
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results->asArray());
    }
    
    /*
     * returns results where any feilds match
     */
    function search ($nameValues, $order=null, $ascending=true) {
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = '".$this->DB->escape($value)."' ";
            }
        }
        $translationTableName = $this->getTranslationTableName();
        $queryBuilder =  $this->getQueryBuilder($this->DB, $this->theTableName())
            ->setFeilds($this->getFeilds())
            ->addJoin("left join", $translationTableName, $translationTableName, $translationTableName.".".self::feild_language." = '".$this->DB->escape($this->getLangaugeCode())."' and ".$translationTableName.".".self::feild_rowid." = '".$this->DB->escape($this->id))
            ->setCondition(implode(" or ", $conditions));
        if ($order !== null && in_array($order, $columnNames)) {
            $queryBuilder->setOrder($order." ".($ascending ? "asc" : "desc"));
        }
        $results = $queryBuilder->getQuery()->execute();
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results->asArray());
    }
    
    function like ($nameValues, $order=null, $ascending=true) {
        $conditions = array();
        $columnNames = array_keys($this->getColumns());
        foreach ($nameValues as $name => $value) {
            if (in_array($name, $columnNames)) {
                $conditions []= $name." = like '%".$this->DB->escape($value)."%' ";
            }
        }
        $translationTableName = $this->getTranslationTableName();
        $queryBuilder =  $this->getQueryBuilder($this->DB, $this->theTableName())
            ->setFeilds($this->getFeilds())
            ->addJoin("left join", $translationTableName, $translationTableName, $translationTableName.".".self::feild_language." = '".$this->DB->escape($this->getLangaugeCode())."' and ".$translationTableName.".".self::feild_rowid." = '".$this->DB->escape($this->id))
            ->setCondition(implode(" or ", $conditions));
        if ($order !== null && in_array($order, $columnNames)) {
            $queryBuilder->setOrder($order." ".($ascending ? "asc" : "desc"));
        }
        $results = $queryBuilder->getQuery()->execute();
        if ($results === null) {
            return null;
        }
        return $this->getInstances($results->asArray());
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
        
    }
    
    function drop () {
        
    }
    
    function getFeilds () {
        $feilds = array(parent::feild_id);
        $translationTableAlias = $this->getTranslationTableName();
        foreach ($this->getColumns() as $name => $attributes) {
            if ($attributes[parent::translatable]) {
                $feilds []= $translationTableAlias.".".$name." as ".$name;
            } else {
                $feilds []= $name." as ".$name;
            }
        }
        implode(", ", $feilds);
    }
    
}