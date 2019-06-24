<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ObjectFactory {
    
    var $DB;
    
    function readDatabase ($outputDirectory) {
        
        $tables = $this->DB->getTableNames();
        
        foreach ($tables as $table) {
            
            $feilds = $this->DB->getTableFeilds($table);
            $attributes = array();
            
            foreach ($feilds as $feild) {
                
                $column = array();
                
                if ((toupper($feild["is_nullable"]) == "YES")) {
                    $column[AbstractTable::nullable] = true;
                }
                
                if (!empty($feild["column_default"])) {
                    if ($feild["column_default"] == "CURRENT_TIMESTAMP") {
                        $column[AbstractTable::default] = array("CURRENT_TIMESTAMP");
                    } else {
                        $column[AbstractTable::default] = $feild["column_default"];
                    }
                }
                
                if ($feild["extra"] == "auto_increment") {
                    $column[AbstractTable::autoIncrement] = true;
                }
                
                switch ($type) {
                    case 'varchar':
                        $column[AbstractTable::type] = AbstractTable::type_string;
                        $column[AbstractTable::length] = $feild["character_maximum_length"];
                        break;
                    case 'blob':
                    case 'clob':
                    case 'text':
                        $column[AbstractTable::type] = AbstractTable::type_text;
                        break;
                    case 'date':
                    case 'datetime':
                        $column[AbstractTable::type] = AbstractTable::type_date;
                        break;
                    case 'timestamp':
                        $column[AbstractTable::type] = AbstractTable::type_timestamp;
                    case 'int':
                    case 'tinyint':
                    case 'bigint':
                    case 'number':
                        $column[AbstractTable::type] = AbstractTable::type_number;
                        $column[AbstractTable::length] = $feild["numeric_precision"];
                        break;
                    case 'float':
                    case 'double':
                    case 'decimal':
                        $column[AbstractTable::type] = AbstractTable::type_decimal;
                        break;
                    case 'boolean':
                    case 'bool':
                    case 'bit':
                        $column[AbstractTable::type] = AbstractTable::type_boolean;
                        break;
                }
                
                $attributes[$feild["column_name"]] = $column;
            }
            $tableClassString = $this->createTableClassString($table,$attributes);
            $filename = $table."Table.php";
            file_put_contents($outputDirectory.$filename, $tableClassString);
        }
    }
    
    function createTableClassString ($tableName, $attributes) {
        
        $fstr = "<?php".PHP_EOL.PHP_EOL;
        
        $upper = strtolower(substr($tableName, 0, 1));
        $nameUpper = substr_replace($tableName, $upper, 0, 1);
        
        $fstr .= "class ".$nameUpper."Table extends $parentType {".PHP_EOL;
        $fstr .= "\tfunction getTableName () {".PHP_EOL;
        $fstr .= "\t\treturn \"$tableName\";".PHP_EOL;
        $fstr .= "\t}".PHP_EOL.PHP_EOL;
        
        $fstr .= "\tfunction getColumns () {".PHP_EOL;
        $fstr .= "\t\treturn array(".PHP_EOL;
        
        $attribArray = array();
        foreach ($attributes as $name => $attribute) {
            
            $str .= "\t\t\t$name => array(".PHP_EOL;
            
            $feildAttribArray = array();
            
            foreach ($attribute as $key => $value) {
                
                switch ($key) {
                    
                    case AbstractTable::autoIncrement:
                        $feildAttribArray []= "\t\t\t\tparent::autoIncrement => ".($value ? "true" : "false");
                        break;
                    case AbstractTable::default:
                        $s = "\t\t\t\tparent::default => ";
                        if (is_array($value)) {
                            if ($value[0] == "CURRENT_TIMESTAMP") {
                                $s .= "array(\"CURRENT_TIMESTAMP\")";
                            }
                        } else {
                            $s .= "\"$value\"";
                        }
                        $feildAttribArray []= $s;
                        break;
                    case AbstractTable::length:
                        $feildAttribArray []= "\t\t\t\tparent::length => $value";
                    case AbstractTable::nullable:
                        $feildAttribArray []= "\t\t\t\tparent::nullable => ".($value ? "true" : "false");
                    case AbstractTable::validator:
                        $s = "\t\t\t\tparent::validator => ";
                        switch ($value) {
                            case AbstractTable::validator_email:
                                $s .= "parent::validator_email";
                                break;
                            case AbstractTable::validator_number:
                                $s .= "parent::validator_number";
                                break;
                            case AbstractTable::validator_expression:
                                $s .= "parent::validator_expression";
                                break;
                        }
                        $feildAttribArray []= $s;
                    case AbstractTable::type:
                        $s = "\t\t\t\tparent::type => ";
                        switch ($value) {
                            case AbstractTable::type_boolean:
                                $s .= "parent::type_boolean";
                                break;
                            case AbstractTable::type_number:
                                $s .= "parent::type_number";
                                break;
                            case AbstractTable::type_decimal:
                                $s .= "parent::type_decimal";
                                break;
                            case AbstractTable::type_date:
                                $s .= "parent::type_date";
                                break;
                            case AbstractTable::type_text:
                                $s .= "parent::type_text";
                                break;
                            case AbstractTable::type_string:
                                $s .= "parent::type_string";
                                break;
                            case AbstractTable::type_timestamp:
                                $s .= "parent::type_timestamp";
                                break;
                        }
                        $feildAttribArray []= $s;
                        break;
                    case AbstractTable::translatable:
                        $feildAttribArray []= "\t\t\t\tparent::translatable => ".($value ? "true" : "false");
                        break;
                }
            }
            $str .= implode(",".PHP_EOL, $feildAttribArray);
            $str .= ")";
            $attribArray []= $str;
        }
        $fstr .= implode(",".PHP_EOL, $feildAttribArray);
        $fstr .= "\t\t);".PHP_EOL;
        $fstr .= "\t}".PHP_EOL;
        $fstr .= "}";
        return $fstr;
    }
}

