<?php

class DmSerializer {
    
    static function getHeaderNames ($fileData,$seperator = ",",$varContainer = "\"") {
        // find seperator
        if(strpos($fileData, "\r\n") >= 0) {
            $lineSeperator = "\r\n";
        } else {
            $lineSeperator = "\n";
        }
        // read the header
        $eolPos = strpos($fileData, $lineSeperator, 0);
        $colNames = array();
        $rowValues = explode($seperator,substr($fileData, 0, $eolPos));
        for ($i=0; $i<count($rowValues); $i++) {
             
            $colName = trim($rowValues[$i], $varContainer);
            $colNames[] = $colName;
        }
        return $colNames;
    }
    
    static function deserialize ($fileData,$seperator = ",",$varContainer = "\"") {
        // find seperator
        if(strpos($fileData, "\r\n") >= 0) {
            $lineSeperator = "\r\n";
        } else {
            $lineSeperator = "\n";
        }
        // read the header
        $colNames = DmSerializer::getHeaderNames($fileData, $seperator, $varContainer);
        $colNamesLen = count($colNames);
        
        // parse the text and return object array
        $arObj = array();
        $first = true;
        $lines = explode($lineSeperator, $fileData);
        foreach ($lines as $line) {
            if (Common::isEmpty(trim($line))) {
                continue;
            }
            if ($first) {
                $first = false;
                continue;
            }
            $rowValues = explode($seperator,$line);
            $obj = array();
            for ($i=0; $i<$colNamesLen; $i++) {
                $rowValues[$i] = trim($rowValues[$i], $varContainer);
                $colName = str_replace(" ", "_", $colNames[$i]);
                $colName = str_replace(".", "_", $colName);
                $obj[$colName] = $rowValues[$i];
            }
            $arObj[] = $obj;
            // unset($obj);
        }
        
        return $arObj;
    }
    
    static function serialize ($headerNames,$valueArray,$seperator = ",",$varContainer = "") {
        
        // write header
        $file = $varContainer.implode($varContainer.$seperator.$varContainer, $headerNames).$varContainer;
        
        // write date
        if (is_array($valueArray)) {
            foreach ($valueArray as $array) {

                if (is_array($array)) {
                    $file .= PHP_EOL;
                    $first = true;
                    foreach ($headerNames as $name) {
                        if (!$first) {
                            $file .= $seperator;
                        }
                        $first = false;
                        if (isset($array[$name])) {
                            $file .= $varContainer.$array[$name].$varContainer;
                        } else {
                            $file .= $varContainer.$varContainer;
                        }
                    }
                }
            }
        }
        return $file;
    }
    
    static function match ($data,$tableId,$names,$columns,$actions,$values,$extTableName,$extColumns,$extObjectId) {
        
        // get virtual and physical columns
        $virtualColumns = VirtualDataModel::getColumns($tableId);
        $dataColumns = count($names);
        $virtualRowNames = array();
        $physicalRowNames = array();
        $vars = get_object_vars($obj);
        for ($i=0; $i<$dataColumns; $i++) {
            $physical = array_search($columns[$i], $extColumns);
            if ($physical) {
                $physicalRowNames[$columns[$i]] = $names[$i];
            } else {
                $virtualRowNames[$columns[$i]] = $names[$i];
            }
        }
        
        // update or insert
        $update = false;
        $matches = array();
        $updates = array();
        $ignores = array();
        for ($i=0; $i<$dataColumns; $i++) {
            if ($actions[$i] == "match") {
                $matches[] = $i;
                $update = true;
            } else if ($actions[$i] == "ignore") {
                $ignores[] = $i;
            } else {
                $updates[] = $i;
            }
        }
        
        // foreach dataset
        foreach ($data as $obj) {
            
            $virtualRowNameValues = array();
            $physicalRowNameValues = array();
            foreach ($virtualRowNames as $columnName => $varName) {
                $virtualRowNameValues[$columnName] = $obj->$varName;
            }
            foreach ($physicalRowNames as $columnName => $varName) {
                $physicalRowNameValues[$columnName] = $obj->$varName;
            }
        
            // update or insert
            if ($update) {
                // update datasets on match
                $virtualMatch;
                $physicalMatch;
                $totalMatch;
                // match each dataset
                $virtMatchedObj = VirtualDataModel::find($tableId,$virtualMatch);
                //
                $dmObject = new DmObject($extTableName,$extTableColumns);
                $physicalMatchedObj = $dmObject->search($physicalMatch);
                
            } else {
                // insert all datasets
                $objectId = VirtualDataModel::insertRow($tableId, $virtualRowNameValues);
                $extColumns[] = $extObjectId;
                $physicalRowNameValues[$extObjectId] = $objectId;
                $dmObject = new DmObject($extTableName,$extColumns);
                $dmObject->create($physicalRowNameValues);
            }
        }
    }
}

?>