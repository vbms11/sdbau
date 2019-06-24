<?php

class Mapping {
    
    
    const tag_table = "table";
    const tag_tableName = "name";
    const tag_tableDescription = "description";
    const tag_tableColumn = "column";
    
    const tag_columnAutoIncrement = "autoIncrement";
    const tag_columnName = "name";
    const tag_columnLabel = "label";
    const tag_columnDescription = "description";
    const tag_columnEditor = "editor";
    const tag_columnType = "type";
    const tag_columnLength = "length";
    const tag_columnNull = "null";
    const tag_columnDefault = "default";
    const tag_columnValidator = "validator";
    const tag_columnTranslatable = "translatable";
    
    const tag_dataList = "data";
    const tag_dataObject = "object";
    const tag_dataColumn = "column";
    
    const attribute_dataObject_type = "table";
    const attribute_dataColumn_name = "column";
    
    function loadMapping ($filename) {
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($filename);
        
        $x = $xmlDoc->documentElement;
        foreach ($x->childNodes AS $item) {
            print $item->nodeName . " = " . $item->nodeValue . "<br>";
        }
        
    }
}

/*

<table>
	<name></name>
	<description></description>
	<column> 
		<autoIncrement/>
		<name></name>
		<label></label>
		<description></description>
		<editor></editor>
		<type></type>
		<length></length>
		<null/>
	</column>
</table>


*/