<?php

/*
    public $online;
    public $changeDate;
    public $createdDate;
    public $tableChangeDate;
 */

abstract class XOjbect {
    
    abstract function getTableName ();
    abstract function getIdFeild ();
    abstract function getFeilds ();
    
    function getId () {
        $idFeild = self::getIdFeild();
        return self::$idFeild;
    }
    function getFeild ($name) {
        $feilds = self::getFeilds();
        if (isset($feilds[$name])) {
            $feild = $feilds[$name];
            if (!isset($feild['name'])) {
                $feild['name'] = $name;
            }
            return $feild;
        }
        return null;
    }
    function getFeildConfig ($name, $value) {
        $feild = self::getFeild($name);
        if (isset($feild[$value])) {
            return $feild[$value];
        }
        return null;
    }
    function getFeildsByConfig ($config, $value) {
        $feilds = array();
        foreach (self::getFeilds() as $name => $feild) {
            if (isset($feild[$config]) && $feild[$config] == $value) {
                $feilds[$name] = $feild;
            }
        }
        return $feilds;
    }
    function getFeildLength ($name) {
        self::getFeildConfig($name,'length');
    }
    function getFeildDefault ($name) {
        self::getFeildConfig($name,'default');
    }
    function getTableDescription () {
        return '';
    }
    function getRequiredFeilds () {
        return self::getFeildsByConfig('required', 'true');
    }
    function getDateFeilds () {
        return self::getFeildsByConfig('type', 'date');
    }
    function getTextFeilds () {
        return self::getFeildsByConfig('type', 'text');
    }
    function getIntFeilds () {
        return self::getFeildsByConfig('type', 'int');
    }
    function getFloatFeilds () {
        return self::getFeildsByConfig('type', 'float');
    }
    function getBooleanFeilds () {
        return self::getFeildsByConfig('type', 'boolean');
    }
    function getRelationFeilds () {
        return self::getFeildsByConfig('type', 'relation');
    }
    static function getFeildValue ($name) {
        return $this->$name;
    }
    static function setFeildValue ($name, $value) {
        $this->$name = $value;
    }
    static function save () {
        if (empty(self::getId())) {
            return self::createObject();
        } else {
            return self::saveObject();
        }
    }
    static function load ($values) {
        foreach (array_keys(self::getFeilds()) as $name) {
            if (isset($values[$name])) {
                self::setFeildValue($name, $values[$name]);
            }
        }
        return $this;
    }
    static function createObject () {
        // create the insert query
        $query = 'insert into '.Config::getTablePrifix().self::getTableName().' (';
        $feilds = self::getFeildNames();
        $query .= implode(',',$feilds);
        $query .= ') values(';
        foreach ($feilds as $feild) {
            if ($feild != current($feilds)) {
                $query .= ',';
            }
            $query .= '\''.Database::escape(self::getFeildValue($feild)).'\'';
        }
        $query .= ')';
        Database::query($query);
        // update the id feild value
        $lastInsertId = Database::queryAsObject('select max(id) as lastid from '.Config::getTablePrifix().self::getTableName());
        self::setFeildValue(self::getIdFeild(),$lastInsertId->lastid);
        return $this;
    }
    static function saveObject () {
        // create the insert query
        $query = 'update '.Config::getTablePrifix().self::getTableName().' set ';
        $feilds = self::getFeildNames();
        $query .= implode(',',$feilds);
        $query .= ') values(';
        foreach ($feilds as $feild) {
            if ($feild != current($feilds)) {
                $query .= ',';
            }
            $query .= $feild.' = \''.Database::escape(self::getFeildValue($feild)).'\'';
        }
        $query .= ' where '.self::getIdFeild().' = \''.self::getFeildValue(self::getIdFeild()).'\'';
        Database::query($query);
        return $this;
    }
    
    static function getByFeild ($name, $value) {
        if (self::hasFeild($name)) {
            $value = Database::escape($value);
            $query = "select from ".Config::getTablePrefix().self::getTableName()." where $name = '$value'";
            if ($name == self::getIdFeild()) {
                return Database::queryAsObject($query);
            }
            return Database::queryAsArray($query);
        }
        throw new Exception("feild name dose not exist");
    }
    
    static function getLikeFeild ($name, $value) {
        if (self::hasFeild($name)) {
            $value = Database::escape($value);
            $query = "select from ".Config::getTablePrefix().self::getTableName()." where $name like '$value'";
            if ($name == self::getIdFeild()) {
                return Database::queryAsObject($query);
            }
            return Database::queryAsArray($query);
        }
        throw new Exception("feild name dose not exist");
    }
    
    function hasFeild ($name) {
        if (in_array($name, self::getFeildNames())) {
            return true;
        }
        return false;
    }
    
    function getObjectEditUrl () {
        return NavigationModel::createStaticPageLink('object',array('mode' => 'edit', 'type' => self::getTableName(), 'id' => self::getFeildValue(self::getIdFeild())));
    }
    
    function getObjectDisplayUrl () {
        return NavigationModel::createStaticPageLink('object',array('mode' => 'display', 'type' => self::getTableName(), 'id' => self::getFeildValue(self::getIdFeild())));
    }
    
    static function getObjectListUrl () {
        return NavigationModel::createStaticPageLink('object',array('mode' => 'list', 'type' => self::getTableName()));
    }
    
    function getObjectImage () {
        
    }
    
    function getObjectDisplayName () {
        
    }
    
    function getObjectDescription () {
        
    }
    
    /**
     * @param type $name
     * @param type $arguments
     * @return type
     * @throws Exception
     */
    function __call($name, $arguments) {
        $feildNames = array_keys(self::getFeilds());
        $methods = get_class_methods(get_class($this));
        if (in_array($name,$methods)) {
            return call_user_method($name, $this, $arguments);
        } else {
            if (strpos('by', $name) === 0) {
                $name = strtolower(substr($name, 2));
                if (in_array($name,$feildNames)) {
                    return self::getByFeild($name,current($arguments));
                }
            }
            if (strpos('like', $name) === 0) {
                $name = strtolower(substr($name, 4));
                if (in_array($name,$feildNames)) {
                    return self::getLikeFeild($name,current($arguments));
                }
            }
            if (strpos('get', $name) === 0) {
                $name = strtolower(substr($name, 3));
                if (in_array($name,$feildNames)) {
                    return self::getFeildValue($name);
                }
            }
            if (strpos('set', $name) === 0) {
                $name = strtolower(substr($name, 3));
                if (in_array($name,$feildNames)) {
                    return self::setFeildValue($name,current($arguments));
                }
            }
            throw new Exception("following method dose not exist: $name");
        }
    }
    
    /**
     * @param type $name
     * @return type
     */
    function __get($name) {
        $value = null;
        if (empty(self::$objectValues)) {
            self::$objectValues = self::$objectCache->get(self::getTableName(),self::getId());
        }
        if (!empty(self::$objectValues[$name])) {
            $value = self::$objectValues[$name];
        }
        if (empty($value)) {
            $feild = self::getFeild($name);
            if (!empty($feild['default'])) {
                $value = $feild['default'];
            }
        }
        return $value;
    }
}

?>