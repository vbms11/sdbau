<?php

/**
 * generates the data access objects from the database schema
 */

class testObject extends XObject {
    public function getTableName () { return "test"; }
    public function getFeilds () {
        return array(
            'test' => array(
                'type' => 'number', 
                'length' => 10, 
                'required' => 'true', 
                'default' => 0
            )
        );
    }
}

class XObjectFactory {
    
    static function generateFromDb () {
        $fileStr = "<?php".PHP_EOL;
        foreach (Database::getTableNames() as $tableName) {
            $tableName = strtolower($tableName);
            $aTableName = strtoupper(substr($tableName, 0, 1)).substr($tableName, 1);
            $fileStr .= 'class '.$aTableName.'Config extends XObject { '.PHP_EOL;
            $fileStr .= '\tpublic getTableName () { '.PHP_EOL.'\t\treturn \''.$tableName.'\'; '.PHP_EOL.'\t}'.PHP_EOL.PHP_EOL;
            $fileStr .= '\tpublic getFeilds () { '.PHP_EOL.'\t\treturn array ('.PHP_EOL;
            $feilds = Database::getTableFeilds($tableName);
            foreach ($feilds as $name => $feild) {
                if ($feild != current($feilds)) {
                    $fileStr .= ',';
                }
                $fileStr .= '\''.$feild->name.'\' => array(';
                $objectConfig = array();
                $typeName = null;
                if (isset($feild->type)) {
                    $type = strtolower($feild->type);
                    switch ($type) {
                        case 'varchar':
                        case 'blob':
                        case 'clob':
                        case 'text':
                            $typeName = 'text';
                            break;
                        case 'date':
                        case 'datetime':
                        case 'timestamp':
                            $typeName = 'date';
                            break;
                        case 'int':
                        case 'tinyint':
                        case 'bigint':
                        case 'number':
                            $typeName = 'int';
                            break;
                        case 'float':
                        case 'double':
                        case 'decimal':
                            $typeName = 'float';
                            break;
                        case 'boolean':
                        case 'bool':
                        case 'bit':
                            $typeName = 'boolean';
                            break;
                    }
                }
                
                if (empty($typeName)) {
                    throw new Exception('unknown type name '.$tableName.'::'.$feild.' == '.$type);
                }
                $objectConfig[] = '\'type\' => \''.$typeName.'\'';
                if (isset($feild->length)) {
                    $objectConfig[] = '\'length\' => \''.$feild->length.'\'';
                }
                if (isset($feild->required) && $feild->required == 'true') {
                    $objectConfig[] = '\'required\' => \'true\'';
                }
                if (isset($feild->default)) {
                    $objectConfig[] = '\'default\' => \''.$feild->default.'\'';
                }
                $fileStr .= implode(',',$objectConfig);
                $fileStr .= '\')';
            }
            $fileStr .= '} '.PHP_EOL;
            file_put_contents('core/model/dao/'.$tableName.'Config.php',$fileStr);
            
            $fileStr = "class ".$aTableName."Object extends ".$aTableName."Config {".PHP_EOL;
            $fileStr .= "\t".PHP_EOL;
            $fileStr .= "}".PHP_EOL;
            $fileStr = "<?php".PHP_EOL.PHP_EOL.$fileStr.PHP_EOL."?>";
            file_put_contents('core/model/custom/'.$tableName.'Object.php',$fileStr);
        }
    }
    
    /**
     * returns table names that have relation to this table
     * @param type $tableName
     */
    function getRelations ($tableName) {
        $relationTables = array();
        foreach (Database::getTableNames() as $tableName) {
            foreach (Database::getTableFeilds($tableName) as $tableFeild) {
                if (isset($tableFeild['relationTable']) && $tableFeild["name"] == $tableName) {
                    $relationTables[] = array(
                        "name" => $tableName,
                        "srcFeild" => $tableFeild['name'],
                        "dstFeild" => $tableFeild['relationFeild']
                    );
                }
            }
        }
        return $relationTables;
    }
    
    
}

?>