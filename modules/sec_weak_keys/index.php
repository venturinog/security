<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-31                                               |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2010-12-21 09:08:11 Alberto Santos asantos@palosanto.com Exp $ */
//include issabel framework
require_once "libs/paloSantoForm.class.php";
require_once "libs/misc.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoWeakKeys.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $dsn_asterisk = generarDSNSistema("asteriskuser","asterisk");
    $pDB = new paloDB($dsn_asterisk);

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "change":
            $content = editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB);
            break;
        case "save":
            $content = saveNewKey($smarty, $module_name, $local_templates_dir, $pDB);
            break;
        default:
            $content = reportWeakKeys($smarty, $module_name, $local_templates_dir, $pDB);
            break;
    }
    return $content;
}

function reportWeakKeys($smarty, $module_name, $local_templates_dir, &$pDB)
{
    require_once "libs/paloSantoGrid.class.php";

    $pWeakKeys = new paloSantoWeakKeys($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $total = $pWeakKeys->getNumWeakKeys($filter_field,$filter_value);
    $oGrid->enableExport();   // enable csv export.
    $oGrid->pagingShow(true); // show paging section.
    $oGrid->setTitle(_tr("Weak Secrets"));
    $oGrid->setIcon("modules/$module_name/images/security_weak_keys.png");
    $oGrid->setNameFile_Export(_tr("Weak Secrets"));
    if($oGrid->isExportAction()){
        $limit = $total;
        $offset = 0;
        $bExportation = true;
    }
    else{
        $limit  = 30;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();
        $bExportation = false;
    }
    $url = array(
        'menu'          =>  $module_name,
        'filter_field'  =>  $filter_field,
        'filter_value'  =>  $filter_value,
    );
    $oGrid -> setURL($url);
    $arrResult =$pWeakKeys->getWeakKeys($limit,$offset,$filter_field,$filter_value);
    if (!is_array($arrResult) && $pWeakKeys->errMsg != "") {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pWeakKeys->errMsg));
    }
    $arrData = null;
    //$arrResult =$pWeakKeys->getWeakKeysChecker();
    $arrColumns = array(_tr("Extension"),_tr("Description"),_tr("Status"));
    $oGrid->setColumns($arrColumns);
    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $tech){
            foreach($tech as $key => $value){
            $arrTmp[0] = $value['id'];
            $arrTmp[1] = $value['description'];
            $mensaje = getMensaje($value['id'],$value['data']);
            if($mensaje != "OK" && !$bExportation)
                $mensaje = _tr("Weak Key").": $mensaje &nbsp;<a href='?menu=$module_name&action=change&id=$value[id]'>"._tr("Change Secret")."</a>";
            $arrTmp[2] = $mensaje;
            $arrData[] = $arrTmp;
            }
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $arrFormFilterWeakKeys = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterWeakKeys);
    $smarty->assign("SHOW", _tr("Show"));

    $_POST["filter_field"]= $filter_field;
    $_POST["filter_value"]= $filter_value;

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Extension")." = ".$filter_value,$_POST, array("filter_field" => "extension","filter_value" => ""));
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter
    $oGrid->showFilter(trim($htmlFilter));
    //end grid parameters

    return $smarty->getTemplateVars('SECURITY_ALERT').$oGrid->fetchGrid();
}

function editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB, $Extension = "", $errors = array())
{
    $id = getParameter("id");
    if($id == "")
        $id = $Extension;

    $arrFormRules = createFieldForm();
    foreach (array('New_Secret','Confirm_New_Secret') as $f) {
        if (!isset($errors[$f]) || $errors[$f] === '') continue;
        if (!isset($arrFormRules[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormRules[$f]['INPUT_EXTRA_PARAM']))
            $arrFormRules[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormRules[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormRules[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormRules[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
    $oForm = new paloForm($smarty,$arrFormRules);
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("ERRORS", array_merge(array('New_Secret' => '', 'Confirm_New_Secret' => ''), $errors));
    $smarty->assign("EXTENSION", $id);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/change.tpl",_tr("Change Key"), array('Extension' => $id));
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $smarty->getTemplateVars('SECURITY_ALERT').$contenidoModulo;
}

function createFieldForm()
{
    $arrFields = array(
            "Extension"       => array( "LABEL"                  => _tr("Extension"),
                                        "REQUIRED"               => "no",
                                        "INPUT_TYPE"             => "TEXT",
                                        "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "readonly" => "readonly"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
                                        "EDITABLE"               => "no",
                                            ),
            "New_Secret"         => array( "LABEL"               => _tr("New Secret"),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "PASSWORD",
                                        "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "autocomplete" => "new-password"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
                                        "EDITABLE"               => "",
                                            ),
            "Confirm_New_Secret" => array( "LABEL"               => _tr("Confirm New Secret"),
                                        "REQUIRED"               => "yes",
                                        "INPUT_TYPE"             => "PASSWORD",
                                        "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "autocomplete" => "new-password"),
                                        "VALIDATION_TYPE"        => "text",
                                        "VALIDATION_EXTRA_PARAM" => "",
                                        "EDITABLE"               => "",
                                            ),
            );
    return $arrFields;
}

function saveNewKey($smarty, $module_name, $local_templates_dir, $pDB)
{
    $arrValues['id'] = getParameter("Extension");
    $arrValues['new_key'] = (string)getParameter("New_Secret");
    $confirmation = (string)getParameter("Confirm_New_Secret");

    $errors = array();
    if ($arrValues['new_key'] === '' || !preg_match('/^[[:alnum:]]{5,}$/', $arrValues['new_key']))
        $errors['New_Secret'] = _tr("The secret must have at least 5 alphanumeric characters");
    if ($confirmation === '')
        $errors['Confirm_New_Secret'] = sprintf(_tr('%s is required'), _tr("Confirm New Secret"));
    elseif ($arrValues['new_key'] !== $confirmation)
        $errors['Confirm_New_Secret'] = _tr("The New Secret does not match with the Confirmation Secret");

    if (count($errors) > 0) {
        return editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB, $arrValues['id'], $errors);
    }

    $mensaje = getMensaje($arrValues['id'],$arrValues['new_key']);
    if($mensaje != "OK"){
        $errors['New_Secret'] = $mensaje;
        return editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB, $arrValues['id'], $errors);
    }

    $pWeakKeys = new paloSantoWeakKeys($pDB);
    $device = $pWeakKeys->getWeakKeyById($arrValues['id']);
    if(!$pWeakKeys->saveNewKey($arrValues,$device['tech'])){
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pWeakKeys->errMsg));
        return editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB, $arrValues['id']);
    }

    return (do_reloadAsterisk($pDB, $smarty))
        ? reportWeakKeys($smarty, $module_name, $local_templates_dir, $pDB)
        : editWeakKeys($smarty, $module_name, $local_templates_dir, $pDB, $arrValues['id']);
}

function do_reloadAsterisk($pDB, $smarty)
{
    require_once "libs/paloSantoConfig.class.php";
    require_once "modules/extensions_batch/libs/paloSantoExtensionsBatch.class.php";

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAMP  = $pConfig->leer_configuracion(false);

    $pConfig = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrAST  = $pConfig->leer_configuracion(false);

    $o = new paloSantoExtensionsBatch($pDB, $arrAST, $arrAMP);
    $r = $o->applyExtensions();
    if ($r) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Successful Secret Update")));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $o->errMsg));
    }
    return $r;
}

function createFieldFilter(){
    $arrFilter = array(
        "extension" => _tr("Extension"),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function getMensaje($extension,$key)
{
    $mensaje = "";
    if($extension == $key)
        $mensaje.=_tr("Same Key");
    if(strlen($key) < 5)
        if($mensaje == "")
            $mensaje.=_tr("Short Key");
        else
            $mensaje.=", "._tr("Short Key");
    if($mensaje == "")
        $mensaje = "OK";
    return $mensaje;
}

function getAction()
{
    if(getParameter("action") == "change")
        return "change";
    else if($_SERVER['REQUEST_METHOD'] === 'POST' && getParameter("save"))
        return "save";
    else
        return "report"; //cancel
}
?>
