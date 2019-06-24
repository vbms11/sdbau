<?php

require_once 'core/util/common.php';
require_once 'core/ddm/dataModel.php';
require_once 'core/ddm/formEditorWrapper.php';

class DynamicDataView {

    static function processAction ($tableId,$backLink,$continueLink) {

        $columns = VirtualDataModel::getColumns($tableId);
        
        if (isset($_GET["ddmAction"])) {
            
            switch ($_GET["ddmAction"]) {
                case "ddmUp":
                    for ($i=0; $i<count($columns); $i++) {
                        if ($columns[$i]->id == $_GET['ddmId']) {
                            if ($i != 0) {
                                VirtualDataModel::setColumnPosition($columns[$i]->id,$columns[$i-1]->position);
                                VirtualDataModel::setColumnPosition($columns[$i-1]->id,$columns[$i]->position);
                                break;
                            }
                        }
                    }
                    break;
                case "ddmDown":
                    for ($i=count($columns)-1; $i>-1; $i--) {
                        if ($columns[$i]->id == $_GET['ddmId']) {
                            if (count($columns)-1 > $i) {
                                VirtualDataModel::setColumnPosition($columns[$i]->id,$columns[$i+1]->position);
                                VirtualDataModel::setColumnPosition($columns[$i+1]->id,$columns[$i]->position);
                                break;
                            }
                        }
                    }
                    break;
                case "ddmSave":
                    $rowNamesValues = array();
                    foreach ($columns as $column) {
                        switch ($column->edittype) {
                            case XDataModel::$dm_type_text:
                            case XDataModel::$dm_type_textbox:
                            case XDataModel::$dm_type_date:
                                $rowNamesValues[$column->name] = $_POST[str_replace(" ", "_", $column->name)];
                                break;
                            case XDataModel::$dm_type_boolean:
                                $rowNamesValues[$column->name] = isset($_POST[str_replace(" ", "_", $column->name)]) ? "1" : "0";
                            break;
                        }
                        
                    }
                    VirtualDataModel::updateRow($tableId, $_POST['objectId'], $rowNamesValues);
                    NavigationModel::redirect($backLink);
                    break;
                case "ddmSaveFeilds":
                    
                    if (!isset($_POST['formEditorValue']))
                        break;
                    
                    $formItems = json_decode($_POST['formEditorValue']);
                    
                    if (empty($formItems))
                        break;
                    
                    // find columns that have been deleted
                    foreach ($columns as $column) {
                        $columnDeleted = true;
                        foreach ($formItems as $formItem) {
                            if ($formItem->id == $column->id)
                                $columnDeleted = false;
                        }
                        if ($columnDeleted) {
                            VirtualDataModel::deleteColumn($tableId, $column->name);
                        }
                    }
                    
                    // save columns
                    $position = 0;
                    foreach ($formItems as $formItem) {
                        
                        $position++;
                        $newColumn = true;
                        foreach ($columns as $column) {
                            if ($formItem->id == $column->id)
                                $newColumn = false;
                        }
                        
                        $id = $formItem->id;
                        $value = $formItem->value;
                        $label = $formItem->label;
                        $required = $formItem->required;
                        $name = $formItem->inputName;
                        $minLength = $formItem->minLength === "" ? null : $formItem->minLength;
                        $maxLength = $formItem->maxLength === "" ? null : $formItem->maxLength;
                        $description = $formItem->description;
                        $validator = FormEditorWrapper::formItemValidatorTovalidator($formItem->validator);
                        $editType = FormEditorWrapper::formItemTypeToeditType($formItem->typeName);
                        
                        if ($newColumn) {
                            VirtualDataModel::addColumn($tableId, $name, $editType, $validator, $position, $label, $description, $minLength, $maxLength, $required, $value);
                        } else {
                            VirtualDataModel::updateColumn($id, $name, $editType, $validator, $position, $description, $required, $label, $minLength, $maxLength, $value);
                        }
                    }
                    
                    NavigationModel::redirect($backLink);
                    break;
                case "ddmDeleteFeild":
                    VirtualDataModel::deleteColumn($tableId,$_GET["ddmId"]);
                    break;
                case "ddmAddAttribute":
                    VirtualDataModel::addColumn($_POST['tableId'], $_POST['name'], $_POST['editType'], null, null);
                    break;
                case "ddmGetValues":
                    $columnValues = VirtualDataModel::getColumnValues($_GET['ddmTableName'],$_GET['ddmColumnName'],$_GET['ddmVirtual']);
                    $returnValue = "[";
                    foreach ($columnValues as $columnValue) {
                        $returnValue .= "'".Common::htmlEscape($columnValue)."'";
                    }
                    $returnValue .= "]";
                    Context::setReturnValue($returnValue);
                    break;
                case "ddmSearch":
                    break;
                case "importConfig":
                    $fileName = ResourcesModel::uploadResource("importFile", "import/csv", array("csv","txt"));
                    $_GET["filename"] = $fileName;
                    break;
                case "exportDownload":
                    $tableId = $_GET['tableId'];
                    $columnNames = explode(",", $_POST['columnNames']);
                    $query = $_SESSION['dataView.query'];
                    $retObjAr = VirtualDataModel::getResultsAsArray($query);
                    $csvData = DmSerializer::serialize($columnNames, $retObjAr);
                    header("content-type: application/csv-tab-delimited-table");
                    header("content-length: ".strlen($csvData));
                    header("content-disposition: attachment; filename=\"$tableId.csv\"");
                    Context::setReturnValue($csvData);
                    break;
                case "import":
                    // parse the file
                    $fileData = file_get_contents(ResourcesModel::getResourcePath("import/csv",$_GET['filename']));
                    $objs = DmSerializer::deserialize($fileData, $_POST['valueSeperator'], $_POST['valueContainer']);
                    
                    // find out how to match the dataset
                    $names = explode(",", $_POST['csvnames']);
                    $column = array(); $action = array(); $value = array();
                    $update = false;
                    $vars = $names;
                    foreach ($vars as $var) {
                        $column[$var] = $_POST["column_$var"];
                        $action[$var] = $_POST["action_$var"];
                        $value[$var] = $_POST["value_$var"];
                        if ($action[$var] == "match") {
                            $update = true;
                        }
                    }
                    
                    // find out virtual columns
                    $virtualColumns = VirtualDataModel::getColumns($tableId);
                    
                    // insert all datasets
                    foreach ($objs as $obj) {

                        // build row name values
                        $rowNamesValues = array();
                        $matchNamesValues = array();
                        foreach ($vars as $var) {
                            switch ($action[$var]) {
                                case "ignore":
                                    continue;
                                    break;
                                case "assign":
                                    if (!Common::isEmpty($value[$var])) {
                                        $rowNamesValues[$column[$var]] = $value[$var];
                                    } else {
                                        $rowNamesValues[$column[$var]] = $obj[$var];
                                    }
                                    break;
                                case "match":
                                    if (!Common::isEmpty($value[$var])) {
                                        $matchNamesValues[$column[$var]] = $value[$var];
                                    } else {
                                        $matchNamesValues[$column[$var]] = $obj[$var];
                                    }
                                    break;
                            }
                        }
                        
                        if ($update) {
                            // update datasets on match or insert if dosent exist
                            $results = VirtualDataModel::find($tableId, $matchNamesValues);
                            if (count($results) > 0) {
                                VirtualDataModel::updateRow($tableId, $results[0]->objectid, $rowNamesValues);
                            } else {
                                VirtualDataModel::insertRow($tableId, $rowNamesValues);
                            }
                        } else {
                            // insert the row
                            VirtualDataModel::insertRow($tableId, $rowNamesValues);
                        }
                        
                    }
                    break;
            }
        }
    }

    static function displayRelation ($rootObjectId) {

    }
    
    static function configureObject ($tableId,$backLink,$continueLink) {
        
        Context::addRequiredScript("resource/js/formEditor/formEditor.js");
        Context::addRequiredStyle("resource/js/formEditor/formEditor.css");
        
        $columns = VirtualDataModel::getColumns($tableId);
        $formItems = FormEditorWrapper::columnsToFormItemConfig($columns);
        $formItemsJson = json_encode($formItems);
        ?>
        <div class="configureObject">
            <div class="formEditor"></div>
            <div class="formEditorValue">
                <form method="post" action="<?php echo NavigationModel::modifyLink($continueLink, array("ddmAction"=>"ddmSaveFeilds")); ?>">
                    <input type="hidden" name="formEditorValue" value='<?php echo $formItemsJson; ?>' />
                </form>
            </div>
            <div class="alignRight">
                <button class="save">Save</button>
                <button class="reset">Reset</button>
                <button class="cancel">Cancel</button>
            </div>
        </div>
        <script type="text/javascript">
        $(".formEditor").formEditor({"optionsLocation":"right","json":$(".configureObject .formEditorValue input[name=formEditorValue]").val()});
        $(".configureObject .alignRight button").button();
        $(".configureObject .alignRight .save").click(function(){
            $(".configureObject .formEditorValue input[name=formEditorValue]").val($(".formEditor").formEditor().toJson());
            $(".configureObject .formEditorValue form").submit();
        });
        $(".configureObject .alignRight .reset").click(function () {
            $(".formEditor").formEditor().fromJson($("configureObject .formEditorValue input[name=formEditorValue]").val());
        });
        $(".configureObject .alignRight .cancel").click(function () {
            callUrl("<?php echo $backLink; ?>");
        });
        </script>
        <?php

    }
    
    static function createObject ($tableId,$createRef=true) {
        $columns = VirtualDataModel::getColumns($tableId);
        $rowNamesValues = array();
        foreach ($columns as $column) {
            $name = str_replace(" ", "_", $column->name);
            if (isset($_POST[$name])) {
                $rowNamesValues[$column->name] = $_POST[$name];
            } else {
                $rowNamesValues[$column->name] = "";
            }
        }
        return VirtualDataModel::insertRow($tableId, $rowNamesValues,$createRef);
    }
    
    static function renderValidateJs ($tableId) {
        $requiredFeilds = array();
        $columns = VirtualDataModel::getColumns($tableId);
        foreach ($columns as $column) {
            if ($column->required == "1") {
                $requiredFeilds[] = Common::htmlEscape(str_replace(" ", "_", $column->name));
            }
        }
        return "['".implode("','", $requiredFeilds)."']";
    }
    
    static function renderCreateObject ($tableId,$backLink,$continueLink) {
        
        $columns = VirtualDataModel::getColumns($tableId);
        $numAttribs = count($columns);
        ?>
        <table width="100%" class="formTable">
            <?php
            for ($i=0; $i<$numAttribs; $i++) {
                $value = "";
                ?>
                <tr>
                <?php
                $name = str_replace(" ", "_", $columns[$i]->label);
                switch ($columns[$i]->edittype) {
                    case XDataModel::$dm_type_text:
                        ?>
                        <td><?php echo $columns[$i]->name." : "; if ($columns[$i]->required == "1") { echo "<span style='color:red;'> * </span>"; }?></td>
                        <td>
                        <input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" class="expand" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/>
                        <?php
                        break;
                    case XDataModel::$dm_type_textbox:
                        ?>
                        <td><?php echo $columns[$i]->name; ?>: </td>
                        <td>
                        <textarea name="<?php echo $name; ?>" id="<?php echo $name; ?>" rows="3" cols="6" class="expand"><?php echo htmlentities($value, ENT_QUOTES); ?></textarea><?php
                        break;
                    case XDataModel::$dm_type_date:
                        ?>
                        <td><?php echo $columns[$i]->name; ?>: </td>
                        <td>
                        <input name="<?php echo $name; ?>" id="<?php echo $name; ?>" type="text" class="expand" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/>
                        <script>
                        $("#<?php echo $name; ?>").datepicker();
                        $("#<?php echo $name; ?>").datepicker( "option", "showAnim", "blind" );
                        </script>
                        <?php
                        break;
                    case XDataModel::$dm_type_boolean:
                        ?>
                        <td></td>
                        <td>
                        <input name="<?php echo $name; ?>" id="<?php echo $name; ?>" type="checkbox" value="1" <?php if ($value == "1") { echo "checked='true'"; } ?> /><?php
                        echo $columns[$i]->name;
                        break;
                    case XDataModel::$dm_type_121:
                        break;
                    case XDataModel::$dm_type_12n:
                        break;
                    case XDataModel::$dm_type_n21:
                        break;
                    case XDataModel::$dm_type_n2n:
                        break;
                    case XDataModel::$dm_type_freetext:
                        ?>
                        <td colspan="2">
                        <?php
                        InfoMessages::printFreetextbox($columns[$i]->description);
                        break;
                    case XDataModel::$dm_type_dropdown:
                        ?>
                        <td><?php echo $columns[$i]->name; ?>: </td>
                        <td>
                        <?php
                        $values = Common::toMap(explode(PHP_EOL, $columns[$i]->description));
                        InputFeilds::printSelect($name, null, $values);
                        break;
                    default:
                }
                
                ?>
                </td>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php
    }

    static function editObject ($tableId,$objectId,$titel,$backLink,$continueLink) {
        $results = VirtualDataModel::getRowByObjectIdAsArray($tableId, $objectId);
        $columns = VirtualDataModel::getColumns($tableId);
        $numAttribs = count($columns);

        if (isset($_GET['ddmAction'])) {
            
            if ($_GET['ddmAction'] == "config" || (isset($_SESSION['ddmMode']) && $_SESSION['ddmMode'] == "config")) {
                $_SESSION['ddmMode'] = "config";
                DynamicDataView::configureObject($tableId,$objectId,"Configure User Attributes:",$backLink,$continueLink);
            }

        } else {
            
            InfoMessages::printInfoMessage("Here you can edit the optional attributes");
            ?>

            <br/>
            <form method="post" id="editObjectForm" action="<?php echo NavigationModel::modifyLink($continueLink,array("ddmAction"=>"ddmSave","object"=>$objectId)); ?>">
                <input type="hidden" name="objectId" value="<?php echo $objectId; ?>">
                <div style="text-align:right;">
                    <button class=".saveButton">Save</button>
                    <button class=".configureButton">Configure</button>
                    <button class=".finnishButton">Finnish</button>
                </div>
                <h3><?php echo $titel; ?></h3>
                <table width="100%">
                    <?php
                    for ($i=0; $i<$numAttribs; $i++) {
                        $value = "";
                        if ($results != null && isset($results[$columns[$i]->name]))
                            $value = $results[$columns[$i]->name];
                        ?>
                        <tr>
                        <?php
                        if ($i == 0) {
                            ?>
                            <td rowspan="<?php echo $numAttribs; ?>" valign="top" style="padding-right:20px;"><img src="resource/img/icons/Pencil.png" alt=""/></td>
                            <?php
                        }
                        ?>
                        <td class="formLabel"><?php echo $columns[$i]->name; ?>: </td>
                        <td class="expand">
                            <?php
                            $name = str_replace(" ", "_", $columns[$i]->name);
                            switch ($columns[$i]->edittype) {
                                case XDataModel::$dm_type_text:
                                    ?><input type="text" name="<?php echo $name; ?>" class="expand" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/><?php
                                    break;
                                case XDataModel::$dm_type_textbox:
                                    ?><textarea name="<?php echo $name; ?>" rows="6" cols="6" class="expand"><?php echo htmlentities($value, ENT_QUOTES); ?></textarea><?php
                                    break;
                                case XDataModel::$dm_type_date:
                                    ?><input name="<?php echo $name; ?>" id="<?php echo $name; ?>" type="text" class="expand" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/>
                                    <script>
                                    $("#<?php echo $name; ?>").datepicker();
                                    $("#<?php echo $name; ?>").datepicker( "option", "showAnim", "blind" );
                                    </script>
                                    <?php
                                    break;
                                case XDataModel::$dm_type_boolean:
                                    ?><input name="<?php echo $name; ?>" type="checkbox" value="1" <?php if ($value == "1") { echo "checked='true'"; } ?> /><?php
                                    break;
                                case XDataModel::$dm_type_freetext:
                                    echo $columns[$i]->description;
                                    break;
                                case XDataModel::$dm_type_121:
                                    break;
                                case XDataModel::$dm_type_12n:
                                    break;
                                case XDataModel::$dm_type_n21:
                                    break;
                                case XDataModel::$dm_type_n2n:
                                    break;
                                default:
                            }
                            ?>
                        </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <hr/>
                <div style="text-align:right;">
                    <button class=".saveButton">Save</button>
                    <button class=".configureButton">Configure</button>
                    <button class=".finnishButton">Finnish</button>
                </div>
            </form>
            <script type="text/javascript">
            $("#editObjectForm button").each(function(key,value){
                $(value).button();
            });
            $(".saveButton").click(function(event) {
                $('#editObjectForm').submit();
            });
            $(".configureButton").click(function(event) {
                callUrl('<?php echo NavigationModel::modifyLink($continueLink,array("ddmAction"=>"config"),false) ?>');
            });
            $(".finnishButton").click(function(event) {
                callUrl("<?php echo NavigationModel::modifyLink($backLink,array(),false); ?>");
            });
            </script>
            <?php

        }
    }

    static function displayObject ($tableId, $objectId) {

        $attribs = VirtualDataModel::getRowByObjectIdAsArray($tableId, $objectId);
        $columns = VirtualDataModel::getColumns($tableId);

        ?>
        <table width="100%">
        <?php

        if ($columns == null) {
            
        } else {
            foreach ($columns as $column) {
                $value = "";
                if ($attribs != null && isset($attribs[$column->name])) {
                    $value = $attribs[$column->name];
                }
                    
                ?>
                <tr>
                    <?php
                    switch ($column->edittype) {
                        case XDataModel::$dm_type_text:
                            ?>
                            <td class="alignRight formLabel"><?php echo $column->name; ?>: </td>
                            <td class="expand"><input class="expand" disabled="true" type="text" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/></td>
                            <?php
                            break;
                        case XDataModel::$dm_type_textbox:
                            ?>
                            <td class="alignRight formLabel"><?php echo $column->name; ?>: </td>
                            <td class="expand"><textarea disabled="true" rows="3" cols="6" class="expand"><?php echo htmlentities($value, ENT_QUOTES); ?></textarea></td>
                            <?php
                            break;
                        case XDataModel::$dm_type_date:
                            ?>
                            <td class="alignRight formLabel"><?php echo $column->name; ?>: </td>
                            <td class="expand"><input disabled="true" type="text" class="expand" value="<?php echo htmlentities($value, ENT_QUOTES); ?>"/></td>
                            <?php
                            break;
                        case XDataModel::$dm_type_boolean:
                            ?>
                            <td class="alignRight formLabel"></td>
                            <td class="expand"><input disabled="true" type="checkbox" value="1" <?php if ($value == "1") { echo "checked='true'"; } ?> /> <?php echo $column->name; ?></td>
                            <?php
                            break;
                        case XDataModel::$dm_type_121:
                            break;
                        case XDataModel::$dm_type_12n:
                            break;
                        case XDataModel::$dm_type_n21:
                            break;
                        case XDataModel::$dm_type_n2n:
                            break;
                        default:
                    }
                    ?>
                </td>
                </tr>
                <?php
            }
        }

        ?>
        </table>
        <?php
    }
    
    static function displayObjectAsString ($tableId, $objectId) {

        $attribs = VirtualDataModel::getRowByObjectIdAsArray($tableId, $objectId);
        $columns = VirtualDataModel::getColumns($tableId);
        $result = "<table width='100%'>";
        
        if ($columns == null) {
            
        } else {
            foreach ($columns as $column) {
                $value = "";
                if ($attribs != null && isset($attribs[$column->name])) {
                    $value = $attribs[$column->name];
                }
                $result .= "<tr>";
                switch ($column->edittype) {
                    case XDataModel::$dm_type_text:
                        $result .= "<td style='text-align:right; white-space:nowrap; width:1%;'>".Common::htmlEscape($column->name).": </td>".
                                   "<td style='width:99%'>".Common::htmlEscape($value)."</td>";
                        break;
                    case XDataModel::$dm_type_textbox:
                        $result .= "<td style='text-align:right; white-space:nowrap; width:1%;'>".Common::htmlEscape($column->name).": </td>".
                                   "<td style='width:99%'>".Common::htmlEscape($value)."</td>";
                        break;
                    case XDataModel::$dm_type_date:
                        $result .= "<td style='text-align:right; white-space:nowrap; width:1%;'>".Common::htmlEscape($column->name).": </td>".
                                   "<td style='width:99%'>".Common::htmlEscape($value)."</td>";
                        break;
                    case XDataModel::$dm_type_boolean:
                        $result .= "<td style='text-align:right; white-space:nowrap; width:1%;'>".Common::htmlEscape($value).": </td>".
                                   "<td style='width:99%'>".Common::htmlEscape($column->name)."</td>";
                        break;
                    case XDataModel::$dm_type_121:
                        break;
                    case XDataModel::$dm_type_12n:
                        break;
                    case XDataModel::$dm_type_n21:
                        break;
                    case XDataModel::$dm_type_n2n:
                        break;
                    default:
                }
                $result .= "</tr>";
            }
        }
        $result .= "</table>";
        return $result;
    }
    
    static function displaySearch ($tableId, $continueLink, $extTableName=null, $extTableColumns=null, $extObjectIdColumn=null) {
        
        if (isset($_GET['ddmAction']) && $_GET['ddmAction'] == "ddmSearch") {
            
            return VirtualDataModel::search($tableId, $namesValues, $extTableName, $extTableColumns, $extObjectIdColumn);
            
        } else {
            $columns = array();
            if ($extTableName != null && $extTableColumns != null && $extObjectIdColumn != null) {
                foreach ($extTableColumns as $extTableColumn) {
                    $column;
                    $column->name = $extTableColumn;
                    $columns[$column->name] = $column;
                }
            }
            
            $virtColumns = VirtualDataModel::getColumns($tableId);
            foreach ($virtColumns as $column) {
                $columns[$column->name] = $column->name;
            }
            
            $actionParams = array("ddmAction"=>"search","ddmTable"=>$tableId,"ddmExtTableName"=>$extTableName,
                "ddmExtTableColumns"=>$extTableColumns,"ddmExtObjectIdColumn"=>$extObjectIdColumn);
            
            
            ?>
            <style>
            .ui-autocomplete {
                    max-height: 150px;
                    overflow-y: auto;
                    overflow-x: hidden;
                    padding-right: 20px;
            }
            * html .ui-autocomplete {
                    height: 150px;
            }
            </style>
            <form type="post" action="<?php echo NavigationModel::modifyLink($continueLink, $actionParams) ?>">
                <table width="100%">
                    <?php
                    $isFirst = true;
                    foreach ($columns as $column) {
                        ?>
                        <tr>
                            <td class="contract"><?php echo $column->name; ?>:</td>
                            <td><input class="expand" name="<?php echo "id_".$column->name; ?>" name="<?php echo $column->name; ?>" type="textbox" /></td>
                            <script type="text/javascript">
                            $("#<?php echo "id_".$column->name; ?>").autocomplete({
                                "source": function(request, response) {
                                    ajaxRequest("<?php echo NavigationModel::modifyLink($continueLink, array("ddmAction"=>"getValues","ddmValueName"=>$column->name)); ?>", function (resText) {
                                        response(resText);
                                    });
                                }
                            });
                            </script>

                        <tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td colspan="2">
                            <input type="submit" value="Search" />
                        </td>
                    <tr>
                </table>
            </form>
            <?php
        }
        return null;
    }
    
    static function renderImport ($backLink, $continueLink, $tableId, $extTableName = null, $extTableColumns = null, $extObjectIdColumn = null) {
        
        $ddmAction = isset($_GET['ddmAction']) ? $_GET['ddmAction'] : null;
        
        switch ($ddmAction) {
            case "importInvoke":
                
                // read the column configs
                $columns = VirtualDataModel::getColumns($tableId);
                $columnsByName = array();
                foreach ($columns as $column) {
                    $column->column = $_POST['column_'.$column->name];
                    $column->action = $_POST['action_'.$column->name];
                    $column->value = $_POST['value_'.$column->name];
                    $columnsByName[$column->name] = $column;
                }
                
                // parse the file
                $fileContent = file_get_contents(ResourcesModel::getResourcePath("import/csv",$_GET['filename']));
                $data = DmSerializer::deserialize($fileContent);
                $physicalRow = array();
                $virtRow = array();
                for ($i=1; $i<count($data); $i++) {
                    $vars = get_object_vars($data);
                    foreach ($vars as $key => $var) {
                        if ($columnsByName[$key]->physical) {
                            $physicalRow[$key] = $var;
                            if ($columnsByName[$key]->action == "match") {
                                $physicalMatch[$key] = $var;
                            }
                        } else {
                            $virtRow[$key] = $var;
                        }
                    }
                    // insert the rows
                    $dmObject = new DmObject($extTableName,$extTableColumns,null);
                    $dmObject->save($physicalMatch,$physicalRow);
                }
                
                $vars = get_object_vars($data);
                break;
            case "importConfig":
                
                $columns = VirtualDataModel::getColumns($tableId);
                $cols = array(); $colType = array(); $colMatch = array();
                
                foreach ($columns as $col) {
                    $colType[$col->name] = $col->name;
                }
                $cols = array_merge($cols,$columns);
                
                $columnNames = array_keys($colType);
                $colsOptions = "";
                foreach ($cols as $col) {
                    $colsOptions .= "<option value='".$col->name."'>".$col->name."</option>";
                }
                
                $fileContent = file_get_contents(ResourcesModel::getResourcePath("import/csv", $_GET['filename']));
                $data = DmSerializer::deserialize($fileContent);
                
                if (count($data) > 0) {
                    $vars = DmSerializer::getHeaderNames($fileContent,$_POST["valueSeperator"],$_POST["valueContainer"]);
                    $allVars = "";
                    foreach ($vars as $var) {
                        $var = str_replace(" ", "_", $var);
                        $var = str_replace(".", "_", $var);
                        if ($allVars != "")
                            $allVars .= ",";
                        $allVars .= $var;
                    }
                    InfoMessages::printInfoMessage("ddm.import.assign");
                    ?>
                    <br/>
                    <form method="post" action="<?php echo NavigationModel::modifyLink($continueLink, array("ddmAction"=>"import","filename"=>$_GET['filename'])); ?>">
                        <input type="hidden" name="csvnames" value="<?php echo $allVars; ?>" />
                        <input type="hidden" name="valueSeperator" value="<?php echo htmlentities($_POST['valueSeperator'],ENT_QUOTES); ?>" />
                        <input type="hidden" name="valueContainer" value='<?php echo htmlentities($_POST['valueContainer'],ENT_QUOTES); ?>' />
                        <table width="100%"><tr><td style="width:50%;">
                               Attribute
                            </td><td style="width:50%;">
                               Column Name
                            </td><td>
                               Action
                            </td><td>
                               Value
                            </td></tr>
                            <?php
                            foreach ($vars as $var) {
                                $var = str_replace(" ", "_", $var);
                                $var = str_replace(".", "_", $var);
                                ?>
                                <tr><td>
                                    <input type="textbox" class="expand" value="<?php echo Common::htmlEscape($var); ?>" disabled="true" />
                                </td><td>
                                    <select name="column_<?php echo Common::htmlEscape($var); ?>" class="expand">
                                        <?php echo $colsOptions; ?>
                                    </select>
                                </td><td>
                                    <select name="action_<?php echo Common::htmlEscape($var); ?>">
                                        <option value="ignore">Ignore</option>
                                        <option value="assign">Assign</option>
                                        <option value="match">Match</option>
                                    </select>
                                </td><td>
                                    <input name="value_<?php echo Common::htmlEscape($var); ?>" type="textbox" value="" />
                                </td></tr>
                                <?php
                            }
                            ?>
                        </table>
                        <hr/>
                        <div class="toolButtonDiv">
                            <input type="button" value="Cancel" />
                            <input type="submit" value="Import" />
                        </div>
                    </form>
                    <?php
                } else {
                    ?>
                    No data found in csv!
                    <?php
                }
                //print_r($data);
                
                
                break;
            case "importInvoke":
                // show statistics
                break;
            default:
                // select file
                InfoMessages::printInfoMessage("ddm.import.upload");
                ?>
                <br/>
                <form action="<?php echo NavigationModel::modifyLink($continueLink, array("ddmAction"=>"importConfig")); ?>" method="post" enctype="multipart/form-data">
                    <b>Value Seperator:</b><br/>
                    <input type="textbox" name="valueSeperator" value=","/>
                    <br/><br/><b>Value Container:</b><br/>
                    <input type="textbox" name="valueContainer" value=""/>
                    <br/><br/><b>Select the csv file:</b><br/>
                    <input type="file" name="importFile" />
                    <br/><br/><hr/>
                    <div class="toolButtonDiv">
                        <input type="submit" value="Upload" />
                    </div>
                </form>
                <?php
        }
        
        
    }
    
    static function renderExport ($backlink, $continueLink, $tableId) {
        
        
        $columns = VirtualDataModel::getColumns($tableId);
        
        switch (isset($_GET['ddmAction']) ? $_GET['ddmAction'] : null) {
            case "doExport":
                
                InfoMessages::printInfoMessage("ddm.export.stats");
                ?>
                <br/>
                
                <?php
                break;
            default:
                
                InfoMessages::printInfoMessage("ddm.export.config");
                array_keys($columns);
                $columnNames = array();
                foreach ($columns as $column) {
                    $columnNames[] = $column->name;
                }
                ?>
                <br/>
                <form method="post" action="<?php echo NavigationModel::modifyLink($continueLink,array("ddmAction"=>"exportDownload","tableId"=>$tableId)); ?>">
                    <div class="toolButtonDiv">
                        <input type="submit" value="Download" />
                        <input type="button" value="Cancel" onclick="callUrl('<?php echo $backlink; ?>');" />
                    </div>
                    <input type="hidden" name="columnNames" value="<?php echo implode(",",$columnNames); ?>">
                    <hr/><br/>
                    <table width="100%"><tr><td class="contract">
                           Attribute
                        </td><td style="width:50%; white-space:nowrap;">
                           Column Name
                        </td><td style="width:50%; white-space:nowrap;">
                           Csv Column Name
                        </td><td colspan="3" align="center" class="contract">
                           Tools
                        </td></tr>
                        <?php
                        $i = 0;
                        foreach ($columns as $column) {
                            $i++;
                            $name = Common::htmlEscape($column->name);
                            ?>
                            <tr><td>
                                <?php echo $i; ?>
                            </td><td>
                                <?php InputFeilds::printSelect("column_".$name, $i, $columnNames); ?>
                            </td><td>
                                <input type="textbox" name="name_<?php echo $name; ?>" value="<?php echo $name; ?>" class="expand" />
                            </td><td>
                                <input type="checkbox" name="<?php echo "include_".$name; ?>" value="1" checked="true" />
                            </td><td>
                                <a href="">
                                    <img src="resource/img/moveup.png" alt="" />
                                </a>
                            </td><td>
                                <a href="">
                                    <img src="resource/img/movedown.png" alt="" />
                                </a>
                            </td></tr>
                            <?php
                        }
                        ?>
                    </table>
                    <br/><hr/>
                    <div class="toolButtonDiv">
                        <input type="submit" value="Download" />
                        <input type="button" value="Cancel" onclick="callUrl('<?php echo $backlink; ?>');" />
                    </div>
                </form>
                <?php
                
                break;
        }
    }
}