<?php

class MysqlDataModel {
    
    static function getColumns ($tableId, $extTableName = null, $extTableColumns = null) {
        
        $columns = array();
        
        if ($extTableName != null && $extTableColumns != null) {
            foreach ($extTableColumns as $extColumn) {
                $columns[$extColumn]->name = $extColumn;
                $columns[$extColumn]->physical = true;
            }
        }
        
        $virtColumns = parent::getColumns($tableId);
        foreach ($virtColumns as $virtColumn) {
            $virtColumn->physical = false;
            $columns[$virtColumn->name] = $virtColumn;
        }
        
        return $columns;
    }
    
    static function swapPhysical ($tableName, $feild, $id1, $id2, $idFeildName=null) {
        
        $tableName = Database::escape($tableName);
        $feild = Database::escape($feild);
        $id1 = Database::escape($id1);
        $id2 = Database::escape($id2);
        
        $selectSql1 = "id = '$id1'";
        $selectSql2 = "id = '$id2'";
        
        $result = Database::query("select '$feild' as oldvalue from $tableName where $selectSql1");
        $obj = mysql_fetch_object($result);
        $oldValue = $obj->oldvalue;
        
        $query = "update $tableName set '$feild' = (select '$feild' from $tableName where $selectSql2) where $selectSql1";
        $query = "update $tableName set '$feild' = '$oldValue' where $selectSql2";
    }
    
    static function searchPhysicalTable ($tableName, $nameValues) {
        
        $query = "select * from '$extTableName' where ";
        
        $first = true;
        foreach ($extNameValues as $extName => $extValue) {
            if (!$first) {
                $query .= "and ";
            }
            $first = false;
            $extName = Database::escape($extName);
            $extValue = Database::escape($extValue);
            $query .= "'$extName' like '%$extValue%' ";
        }
        
        $result = Database::query($query);
        $physicalRows = array();
        while ($obj = mysql_fetch_object($result)) {
            $physicalRows[] = $obj;
        }
        
        return $physicalRows;
    }
}

?>