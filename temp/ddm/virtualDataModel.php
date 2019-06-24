<?php

class VirtualDataModel extends XDataModel   {
    
    static function install () {
        
    }
    
    // tables
    static function createTable ($tableName) {
        $tableName = Database::escape($tableName);
        Database::query("insert into t_vdb_table (name) values ('$tableName')");
        $ret = Database::queryAsObject("select last_insert_id() as newid from t_vdb_table");
        return $ret->newid;
    }
    static function deleteTable ($tableName) {
        $tableName = Database::escape($tableName);
        Database::query("delete from t_vdb_table where name = '$tableName'");
    }
    static function deleteTableById ($tableId) {
        $tableId = Database::escape($tableId);
        Database::query("delete from t_vdb_table where id = '$tableId'");
    }
    static function getTables () {
        return Database::queryAsArray("select * from t_vdb_table where system = '0' order by name asc");
    }
    static function getSystemTables () {
        return Database::queryAsArray("select * from t_vdb_table order by name asc");
    }
    static function getTable ($tableName) {
        $tableName = Database::escape($tableName);
        return Database::queryAsObject("select * from t_vdb_table where name = '$tableName'");
    }
    static function getTableById ($tableName) {
        $tableName = Database::escape($tableName);
        return Database::queryAsObject("select * from t_vdb_table where id = '$tableName'");
    }
    
    // columns
    static function addColumn ($tableName, $columnName, $editType, $validator = '', $position=null, $label = "", $description = "", $minLength = '', $maxLength = '', $required = "0", $value = "") {
        
        $tableName = Database::escape($tableName);
        $columnName = Database::escape($columnName);
        $editType = Database::escape($editType);
        $label = Database::escape($label);
        $description = Database::escape($description);
        $required = Database::escape($required);
        $value = Database::escape($value);
        $minLength = Database::escape($minLength);
        $maxLength = Database::escape($maxLength);
        $validator = Database::escape($validator);
        if ($position != null) {
            $position = Database::escape($position);
        } else {
            $position = VirtualDataModel::getNexColumnPosition($tableName);
        }
        Database::query("insert into t_vdb_column
            (tableid,name,edittype,position,label,description,required,minlength,maxlength,value,validator)
            values ((select id from t_vdb_table where name = '$tableName'),'$columnName','$editType','$position','$label','$description','$required','$minLength','$maxLength','$value','$validator')");
    }
    static function deleteColumn ($tableName, $columnName) {
        
        $tableName = Database::escape($tableName);
        $columnName = Database::escape($columnName);
        // delete values this column has
        Database::query("delete from t_vdb_value where columnid = (select c.id from t_vdb_column c where c.name = '$columnName')");
        // delete the column
        Database::query("delete from t_vdb_column where tableid = (select t.id from t_vdb_table t where t.name = '$tableName') and name = '$columnName'") or die(mysql_error());
        
    }
    static function updateColumn ($id, $columnName, $editType, $validator=null, $position=null, $description="", $required="0", $label = "",  $minLength = null, $maxLength = null, $value = "") {
        
        $columnName = Database::escape($columnName);
        $editType = Database::escape($editType);
        $id = Database::escape($id);
        $description = Database::escape($description);
        $required = Database::escape($required);
        $label = Database::escape($label);
        $value = Database::escape($value);
        $minLength = Database::escape($minLength);
        $maxLength = Database::escape($maxLength);
        $validator = Database::escape($validator);
        Database::query("update t_vdb_column set name = '$columnName', edittype = '$editType', description = '$description', required = '$required', label = '$label', value = '$value', minlength = '$minLength', maxlength = '$maxLength', validator = '$validator' where id = '$id'");
    }
    static function getColumns ($tableName) {
        $tableName = Database::escape($tableName);
        return Database::queryAsArray("
                select c.*, rt.physical as refcolphysical, rt.id as refcoltableid, rc.name as refcolname, rc.objectidcolumn as refcolobjectid, rt.name as reftablename, rcoc.name as refcolobjectidname
                from t_vdb_column c
                left join t_vdb_column rc on rc.id = c.refcolumn
                left join t_vdb_table rt on rt.id = rc.tableid
                left join t_vdb_column rcoc on rcoc.id = c.objectidcolumn
                where c.tableid = (select t.id from t_vdb_table t where t.name = '$tableName') order by c.position");
    }
    static function getColumn ($tableName,$columnName) {
        $tableName = Database::escape($tableName);
        $columnName = Database::escape($columnName);
        return Database::queryAsObject("select c.* from t_vdb_column c where c.name = '$columnName' and c.tableid = (select t.id from t_vdb_table t where t.name = '$tableName')");
    }
    static function getColumnById ($columnId) {
        $columnId = Database::escape($columnId);
        return Database::queryAsObject("select c.* from t_vdb_column c where c.id = '$columnId'");
    }
    static function hasColumn ($tableName, $columnName) {
        $tableName = Database::escape($tableName);
        $columnName = Database::escape($columnName);
        $obj = Database::queryAsObject("select 1 from t_vdb_column c where c.name = '$columnName' and c.tableid = (select t.id from t_vdb_table t where t.name = '$tableName')");
        return $obj != null ? true : false;
    }
    
    static function loadRefTable ($tableName,$objectId = null) {
        $columns = VirtualDataModel::getColumns($tableName);
        foreach ($columns as $column) {
            if ($objectId == null) {
                // load all results
            } else {
                // load single result
                
                if ($column->refcolumn != null && $column->refcolphysical == "1") {
                    $refTableName = $column->reftablename;
                    $dmObject = new DmObject($refTableName,array($column->refcolname,$column->refcolobjectidname));
                    $result = $dmObject->getByColumn($column->refcolobjectidname, $objectId);
                    $result = $result[0];
                    $refColName = $column->refcolname;
                    $rowNamesValues[$refColName] = $result->$refColName;
                    VirtualDataModel::updateRow($tableName, $objectId, $rowNamesValues);
                }
            }
        }
    }
    
    // row operations
    static function insertRow ($tableName, $rowNamesValues, $createRef=true) {
        // get the table id
        $table = VirtualDataModel::getTable($tableName);
        $tableId = $table->id;
        // create the object
        Database::query("insert into t_vdb_object(tableid) values ('$tableId')");
        $lastInsert = Database::queryAsObject("select last_insert_id() as objectid from t_vdb_object");
        $objectId = $lastInsert->objectid;
        
        $columns = VirtualDataModel::getColumns($tableName);
        $refColumns = array();
        $refTableName = null;
        $refObjectId = null;
        
        $first = true;
        $insertSql = "insert into t_vdb_value (objectid,columnid,value) values ";
        foreach ($columns as $column) {
            if (isset($rowNamesValues[$column->name])) {
                $columnValue = Database::escape($rowNamesValues[$column->name]);
                // sql for physical db
                if ($column->refcolumn != null && $column->refcolphysical == "1") {
                    $refColumns[$column->refcolname] = $columnValue;
                    $refTableName = $column->reftablename;
                    if ($column->refcolobjectidname != null) {
                        $refObjectId = $column->refcolobjectidname;
                    }
                }
                // sql for virtual db
                $insertSql .= $first ? "" : ", ";
                $insertSql .= "('$objectId','".$column->id."','$columnValue')";
                $first = false;
            }
        }
        
        // create the physical table
        if ($createRef && count($refColumns) > 0) {
            $refColumns[$refObjectId] = $objectId;
            $dbObj = new DmObject($refTableName, array_keys($refColumns));
            $id = $dbObj->create($refColumns);
            
        }
        
        // run the insert querys
        if ($first == false) {
            Database::query($insertSql);
        }
        
        //echo $insertSql;
        return $objectId;
    }
    static function insertRows ($tableName, $ar_rowNamesValues) {
        // get the table id
        $table = VirtualDataModel::getTable($tableName);
        $columnId = $column->id;
        $tableId = $table->id;
        $insertValuesSql = "";
        //
        $columns = VirtualDataModel::getColumns($tableName);
        $columnsByName = array();
        foreach ($columns as $column){
            $columnsByName[$column->name] = $column->id;
        }
        
        // create the object
        foreach ($ar_rowNamesValues as $rowNamesValues) {
            
            Database::query("insert into t_vdb_object(tableid) values ('$tableId')");
            $lastInsert = Database::queryAsObject("select last_insert_id() as objectid from t_vdb_object");
            $objectId = $lastInsert->objectid;
            $first = true;
            foreach ($rowNamesValues as $columnName => $columnValue) {
                $columnId = $columnsByName[$columnName];
                $columnValue = Database::escape($columnValue);
                if(!$first) {
                    $insertValuesSql .= ",";
                }
                $insertValuesSql .= "('$objectId','$columnId','$columnValue')";
                $first = false;
            }
            
        }
        Database::query("insert into t_vdb_value (objectid,columnid,value) values $insertValuesSql");
        return $objectId;
    }
    static function deleteRow ($tableName, $objectId) {
        $tableName = Database::escape($tableName);
        $objectId = Database::escape($objectId);
        Database::query("delete from t_vdb_value where objectid = '$objectId'");
        Database::query("delete from t_vdb_object where id = '$objectId'");
    }
    static function setRowViewed ($tableName, $objectId, $viewed = '1') {
        $tableName = Database::escape($tableName);
        $objectId = Database::escape($objectId);
        $viewed = Database::escape($viewed);
        Database::query("update t_vdb_object set viewed = '$viewed' where id = '$objectId'");
    }
    static function updateRow ($tableName, $objectId, $rowNamesValues) {
        $objectId = Database::escape($objectId);
        $table = VirtualDataModel::getTable($tableName);
        $tableId = $table->id;
        $columns = VirtualDataModel::getColumns($tableName);
        foreach ($columns as $column) {
            if (isset($rowNamesValues[$column->name])) {
                $columnValue = Database::escape($rowNamesValues[$column->name]);
                if ($column->refcolphysical != null) {
                    // save physicaal column
                    $reftablename = $column->reftablename;
                    $refcolobjectidname = $column->refcolobjectidname;
                    $refcolname = $column->refcolname;
                    Database::query("update $reftablename set $refcolname = '$columnValue' where objectid = '$objectId'");
                }
                $columnId = Database::escape($column->id);
                $hasValue = Database::queryAsObject("select 1 from t_vdb_value where objectid = '$objectId' and columnid = '$columnId'");
                if ($hasValue != null) {
                    Database::query("update t_vdb_value set value = '$columnValue' where columnid = '$columnId' and objectid = '$objectId'");
                }  else {
                    Database::query("insert into t_vdb_value (objectid,columnid,value) values ('$objectId','$columnId','$columnValue')");
                }
                
            }
        }
    }
    static function getAllRows ($tableName) {
        $tableName = Database::escape($tableName);
        $result = Database::queryAsArray(
            "select v.value, c.name, v.objectid
                from t_vdb_value v
                join t_vdb_column c on v.columnid = c.id
                where c.tableid = (select t.id from t_vdb_table t where t.name = '$tableName')
                order by v.objectid");
        $ret = array(); $row; $lastObjectId = null;
        if (count($result) > 0) {
            foreach ($result as $obj) {
                if ($lastObjectId != null && $obj->objectid != $lastObjectId) {
                    $lastObjectId = $obj->objectId;
                    $ret[] = $row;
                    unset($row);
                }
                $lastObjectId = $obj->objectid;
                $row->$obj->name = $obj->value;
            }
            $ret[] = $row;
        }
        return $ret;
    }
    static function getCountViewed ($viewed) {
        $viewed = Database::escape($viewed);
        $result = Database::queryAsObject(
            "SELECT count(o.id) as count
            FROM t_vdb_object o
            join t_vdb_table t on o.tableid = t.id
            WHERE o.viewed = '$viewed' and t.system = '0'");
        return $result->count;
    }
    static function getRowsViewed ($tableName,$viewed) {
        $viewed = Database::escape($viewed);
        $tableName = Database::escape($tableName);
        $result = Database::queryAsArray(
            "SELECT v.value, c.name, v.objectid, o.viewed
            FROM t_vdb_object o
            JOIN t_vdb_value v on v.objectid = o.id
            JOIN t_vdb_column c ON v.columnid = c.id
            WHERE c.tableid = (
                SELECT t.id
                FROM t_vdb_table t
                WHERE t.name = '$tableName')
            and o.viewed = '$viewed'
            ORDER BY o.id");
        $ret = array(); $row = array();
        if (count($result) > 0) {
            foreach ($result as $obj) {
                if (isset($row['objectid']) && $obj->objectid != $row['objectid']) {
                    $ret[] = $row;
                    unset($row);
                    $row = array();
                }
                $row['objectid'] = $obj->objectid;
                $row[$obj->name] = $obj->value;
            }
            $ret[] = $row;
        }
        return $ret;
        
    }
    static function getAllRowsAsArray ($tableName) {
        $tableName = Database::escape($tableName);
        $result = Database::queryAsArray(
            "select v.value, c.name, v.objectid
                from t_vdb_value v
                join t_vdb_column c on v.columnid = c.id
                where c.tableid = (select t.id from t_vdb_table t where t.name = '$tableName')
                order by v.objectid");
        $ret = array(); $row = array(); $lastObjectId = null;
        if (count($result) > 0) {
            foreach ($result as $obj) {
                if ($lastObjectId != null && $obj->objectid != $lastObjectId) {
                    $row['objectid'] = $lastObjectId;
                    $lastObjectId = $obj->objectid;
                    $ret[] = $row;
                    unset($row);
                    $row = array();
                }
                $lastObjectId = $obj->objectid;
                $row[$obj->name] = $obj->value;
            }
            $ret[] = $row;
        }
        return $ret;
    }
    static function getRowByObjectId ($tableName, $objectId) {
        if (is_array($objectId)) {
            foreach ($objectId as $key => $id) {
                $objectId[$key] = Database::escape($id);
            }
            $objectId = "in ('".implode("','",$objectId)."')";
        } else {
            $objectId = "= '".Database::escape($objectId)."'";
        }
        $results = Database::queryAsObject("select v.value as value, c.name as name from t_vdb_value v join t_vdb_column c on v.columnid = c.id where v.objectid $objectId");
        $row;
        foreach ($results as $result) {
            // var_dump($result);
            $row->$result->name = $result->value;
        }
        return $row;
    }
    static function getRowByObjectIdAsArray ($tableName, $objectId) {
        
        if (is_array($objectId)) {
            foreach ($objectId as $key => $id) {
                $objectId[$key] = Database::escape($id);
            }
            $objectId = "in ('".implode("','",$objectId)."')";
        } else {
            $objectId = "= '".Database::escape($objectId)."'";
        }
        $results = Database::queryAsArray("select v.value, v.objectid, c.name from t_vdb_value v join t_vdb_column c on v.columnid = c.id where v.objectid $objectId ");
        
        $row = array();
        if (is_array($objectId)) {
            foreach ($results as $result) {
                if (!isset($row[$result->objectid])) {
                    $row[$result->objectid] = array();
                }
                $row[$result->objectid][$result->name] = $result->value;
            }
        } else {
            foreach ($results as $result) {
                $row[$result->name] = $result->value;
            }
        }
        return $row;
    }
    
    // search operations
    static function getResults ($query) {
        $ret = array();
        // get ids of columns
        $objectIds = VirtualDataModel::parseCriteria($query->getTable(),$query->getCriteria());
        // get results
        if (count($objectIds) > 0) {
            $ret = VirtualDataModel::getRowByObjectIds($query->getTable(),$objectIds);
        }
        return $ret;
    }
    static function getResultsAsArray ($query) {
        $ret = array();
        // get ids of columns
        $objectIds = VirtualDataModel::parseCriteria($query->getTable(),$query->getCriteria());
        // get results
        if (count($objectIds) > 0) {
            $ret = VirtualDataModel::getRowByObjectIdAsArray($query->getTable(),$objectIds);
        }
        return $ret;
    }
    
    // search
    static function search ($tableName, $namesValues) {
        $result = array(); $criterion = array();
        foreach ($namesValues as $columnName => $value) {
            $criterion[] = DMCriteria::like($value, $columnName);
        }
        if (count($criterion) > 0) {
            $query = new DMQuery($tableName,DMCriteria::addAnd($criterion));
            VirtualDataModel::getResults($query);
        }
        return $result;
    }
    
    // find
    static function find ($tableName, $columnNamesValues) {
        $criteria = array();
        foreach ($columnNamesValues as $name => $value) {
            $criteria[] = DMCriteria::equals($value, $name);
        }
        $query = new DMQuery($tableName,DMCriteria::addAnd($criteria));
        return VirtualDataModel::getResults($query);
    }
    
    // unique values
    static function getColumnValues ($tableName,$columnName) {
        $tableName = Database::escape($tableName);
        $columnName = Database::escape($columnName);
        $values = Database::queryAsArray("select distinct v.value as colval from t_vdb_column as c
                 left join t_vdb_value as v on c.id = v.columnid
                 where c.name = '$columnName' and c.tableid = (select t.id from t_vdb_table where t.name = '$tableName')");
        return $values;
    }
    
    // extra methods
    static function getRowByObjectIds ($tableName, $objectIds) {
        $condition = "('".implode("','", $objectIds)."')";
        $results = Database::queryAsArray("select v.value, c.name, v.objectid from t_vdb_value v join t_vdb_column c on v.columnid = c.id where v.objectid in $condition order by v.objectid");
        $ret = array(); $row; $lastObjectId = null;
        foreach ($result as $obj) {
            if ($lastObjectId != null && $obj->objectid != $lastObjectId) {
                $row->objectid = $obj->objectid;
                $ret[] = $row;
                unset($row);
            }
            $lastObjectId = $obj->objectid;
            $row->$obj->name = $obj->value;
        }
        if (count($result) > 0) {
            $row->objectid = $lastObjectId;
            $ret[] = $row;
        }
        return $ret;
    }
    static function parseCriteria ($tableName,$criteria) {
        $hitObjectIds = null;
        switch ($criteria->operand) {
            case DMCriteria::$dm_and:
                foreach ($criteria->value as $criterion) {
                    $objectIds = VirtualDataModel::parseCriteria($tableName,$criterion);
                    if ($objectIds == null || count($objectIds) == 0) {
                        return array();
                    }
                    if ($hitObjectIds == null) {
                        $hitObjectIds = $objectIds;
                    } else {
                        $hitObjectIds = array_intersect($hitObjectIds,$objectIds);
                    }
                    if (count($hitObjectIds) == 0) {
                        return array();
                    }
                }
                break;
            case DMCriteria::$dm_or:
                foreach ($criteria->value as $criterion) {
                    $objectIds = VirtualDataModel::parseCriteria($tableName,$criterion);
                    if ($hitObjectIds == null) {
                        $hitObjectIds = $objectIds;
                    } else {
                        $hitObjectIds = array_merge($objectIds,$hitObjectIds);
                        $hitObjectIds = array_unique($hitObjectIds);
                    }
                }
                break;
            case DMCriteria::$dm_all:
                $hitObjectIds = array();
                $allRows = VirtualDataModel::getAllRows($tableName);
                foreach ($allRows as $row) {
                    $hitObjectIds[] = $row->objectid;
                }
                break;
            default:
                $results = VirtualDataModel::getObjectId($tableName, $criteria->columnName, $criteria->operand, $criteria->value);
                foreach ($results as $objectId) {
                    $hitObjectIds[] = $objectId->objectid;
                }
                break;
                
        }
        return $hitObjectIds;
    }
    static function getObjectId ($table,$feild,$operand,$value) {
        $table = Database::escape($table);
        $feild = Database::escape($feild);
        $value = Database::escape($value);
        $selectObjectsSql = "select o.id as objectid from t_vdb_object o
         join t_vdb_table t on t.id = o.tableid and t.name = '$table'
         join t_vdb_value v on o.id = v.objectid and '$value' $operand v.value
         join t_vdb_column c on c.id = v.columnid and c.name = '$feild'";
        return Database::queryAsArray($selectObjectsSql);
    }
    
    static function getNexColumnPosition ($tableId) {
        $tableId = Database::escape($tableId);
        $result = Database::query("select max(position) as nextposition from t_vdb_column where tableid = (select id from t_vdb_table where name = '$tableId')");
        $obj = mysql_fetch_object($result);
        return $obj->nextposition + 1;
    }
    
    static function setColumnPosition ($columnId,$position) {
        $columnId = Database::escape($columnId);
        $position = Database::escape($position);
        Database::query("update t_vdb_column set position = '$position' where id = '$columnId'");
    }
}

?>