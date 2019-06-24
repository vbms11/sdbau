<?php

class FormEditorWrapper {
    
    static function formItemTypeToeditType($typeName) {
        $edittype = "";
        switch ($typeName) {
            case "FormItemInput":
                $edittype = XDataModel::$dm_type_text;
                break;
            case "FormItemTextArea":
                $edittype = XDataModel::$dm_type_textbox;
                break;
            case "FormItemDate":
                $edittype = XDataModel::$dm_type_date;
                break;
            case "FormItemCheckbox":
                $edittype = XDataModel::$dm_type_boolean;
                break;
            case "FormItemSelect":
                $edittype = XDataModel::$dm_type_dropdown;
                break;
        }
        return $edittype;
    }
    
    static function editTypeToFormItemType ($edittype) {
        $formItemType = "";
        switch ($edittype) {
            case XDataModel::$dm_type_text:
                $formItemType = "FormItemInput";
                break;
            case XDataModel::$dm_type_textbox:
                $formItemType = "FormItemTextArea";
                break;
            case XDataModel::$dm_type_date:
                $formItemType = "FormItemDate";
                break;
            case XDataModel::$dm_type_boolean:
                $formItemType = "FormItemCheckbox";
                break;
            case XDataModel::$dm_type_dropdown:
                $formItemType = "FormItemSelect";
                break;
        }
        return $formItemType;
    }
    
    static function formItemValidatorTovalidator($validator) {
        $editType = "";
        switch ($validator) {
            case "FormItemInput":
                $editType = XDataModel::$dm_type_text;
                break;
            case "FormItemTextArea":
                $editType = XDataModel::$dm_type_textbox;
                break;
            case "FormItemDate":
                $editType = XDataModel::$dm_type_date;
                break;
            case "FormItemCheckbox":
                $editType = XDataModel::$dm_type_boolean;
                break;
            case "FormItemSelect":
                $editType = XDataModel::$dm_type_dropdown;
                break;
        }
        return $editType;
    }
    
    static function validatorToFormItemValidator ($validator) {
        $formValidator = "";
        switch ($validator) {
            case XDataModel::$dm_validator_none:
                $formValidator = "none";
                break;
            case XDataModel::$dm_validator_text:
                $formValidator = "text";
                break;
            case XDataModel::$dm_validator_alphabetic:
                $formValidator = "alpha";
                break;
            case XDataModel::$dm_validator_numeric:
                $formValidator = "numbers";
                break;
            case XDataModel::$dm_validator_email:
                $formValidator = "email";
                break;
        }
        return $formValidator;
    }
    
    static function columnToFormItemConfig ($column) {
        
        $formItem = array();
        $formItem["id"] = $column->id;
        $formItem["domId"] = "formItem_".$column->id;
        $formItem["value"] = $column->value;
        $formItem["label"] = $column->label;
        $formItem["required"] = $column->required;
        $formItem["inputName"] = $column->name;
        $formItem["minLength"] = $column->minlength;
        $formItem["maxLength"] = $column->maxlength;
        $formItem["description"] = $column->description;
        $formItem["validator"] = self::validatorToFormItemValidator($column->validator);
        $formItem["typeName"] = self::editTypeToFormItemType($column->edittype);
        return $formItem; 
    }
    
    static function columnsToFormItemConfig ($columns) {
        $formItems = array();
        foreach ($columns as $column) {
            $formItems[] = self::columnToFormItemConfig($column);
        }
        return $formItems;
    }
    
    
}

?>