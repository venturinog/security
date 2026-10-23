<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2020 Issabel Foundation                                |
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
  $Id: index.php, Mon 12 Jul 2021 04:54:16 PM EDT, nicolas@issabel.com
*/
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/Issabelwhitelist.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $smarty->assign('SECURITY_ALERT', '');

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case 'save':
            $content = savewhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'save_edit':
            $content = savewhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, true);
            break;
        case 'new': case 'view': case 'edit':
            $content = newwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $action);
            break;
        case 'delete':
            $content = deletewhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'cancel':
            $content = reportwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportwhitelist($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pwhitelist = new Issabelwhitelist($pDB);

    $pwhitelist->checkTable();

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->addNew("new",_tr("Add IP Address"));
    $oGrid->deleteList(_tr("Are you sure you wish to delete the IP address?"),"delete",_tr("Delete"));
    $oGrid->setTitle(_tr("White List"));
    $oGrid->pagingShow(true); // show paging section.

    $url = array(
        "menu"         =>  $module_name,
    );
    $oGrid->setURL($url);

    $arrColumns = array("",_tr("IP Address"),_tr("Note"),_tr("Options"));
    $oGrid->setColumns($arrColumns);

    $total   = $pwhitelist->getNumwhitelist();
    $arrData = null;
    if($oGrid->isExportAction()){
        $limit  = $total; // max number of rows.
        $offset = 0;      // since the start.
    }
    else{
        $limit  = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();
    }

    $arrResult =$pwhitelist->getwhitelist($limit, $offset);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){
            $ip = htmlspecialchars($value['ip_address'], ENT_COMPAT, 'UTF-8');
            $arrTmp[0] = "<input type='checkbox' name='del_x[]' value='".$ip."'>";
            $arrTmp[1] = $ip;
            $arrTmp[2] = htmlspecialchars($value['note'], ENT_COMPAT, 'UTF-8');
            $arrTmp[3] = "&nbsp;<a href='?menu=$module_name&action=view&id=".urlencode($value['ip_address'])."'>"._tr('View')."</a>";
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $smarty->getTemplateVars('SECURITY_ALERT').$content;
}

function newwhitelist($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $action, $errors = array(), $data = array())
{
    $pwhitelist = new Issabelwhitelist($pDB);
    $pwhitelist->checkTable();

    // En vista/edición se parte de la fila guardada; `id` es la IP original.
    if (($action === 'view' || $action === 'edit') && count($data) === 0) {
        $id = getParameter('id');
        $row = $pwhitelist->getwhitelistByIp($id);
        if (is_array($row)) {
            $data = array('ip_address' => $row['ip_address'], 'note' => $row['note'], 'id' => $row['ip_address']);
        }
    }

    $arrFormNew = createFieldForm();
    // Recuadro rojo por campo (conservando form-control).
    foreach (array('ip_address','note') as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormNew[$f]['INPUT_EXTRA_PARAM']))
            $arrFormNew[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
    $oForm = new paloForm($smarty, $arrFormNew);

    $titulo = _tr('Define IP Address');
    if ($action === 'view') $titulo = _tr('View IP Address');
    if ($action === 'edit') $titulo = _tr('Edit IP Address');

    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("BACK", _tr("Back"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("MODE", $action);
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("ERRORS", array_merge(array('ip_address' => '', 'note' => ''), $errors));
    $smarty->assign("VALUES", array_merge(array('ip_address' => '', 'note' => '', 'id' => ''), $data));

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", $titulo, $data);
    $contenidoModulo = "<form method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $smarty->getTemplateVars('SECURITY_ALERT').$contenidoModulo;
}

function deletewhitelist($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $oIssa = new Issabelwhitelist($pDB);
    $str_msj_error = "";
    $ips = isset($_POST['del_x']) && is_array($_POST['del_x']) ? $_POST['del_x'] : array();

    if (count($ips) === 0) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('warning', _tr('Message'), _tr('No records were selected')));
        return reportwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    foreach( $ips as $value ){
        if( $oIssa->deletewhitelist($value) == false ) {
            $str_msj_error .= htmlspecialchars((string)$oIssa->errMsg, ENT_COMPAT, 'UTF-8')."<br />";
        }
    }

    if( strlen($str_msj_error) == 0 ){
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'), _tr('Delete correctly')));
    }
    else{
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'), $str_msj_error, array('html' => true)));
    }

    return reportwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function savewhitelist($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $isEdit = false)
{
    $oIssa = new Issabelwhitelist($pDB);
    $oIssa->checkTable();

    $ip_address = isset($_POST['ip_address']) ? trim((string)$_POST['ip_address']) : '';
    $note       = isset($_POST['note']) ? trim((string)$_POST['note']) : '';
    $old_ip     = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
    $action     = $isEdit ? 'edit' : 'new';

    $errors = array();
    if ($ip_address === '') {
        $errors['ip_address'] = sprintf(_tr('%s is required'), _tr('IP Address'));
    } else if ($oIssa->validateIpOrMask($ip_address) !== '') {
        $errors['ip_address'] = sprintf(_tr('%s is invalid'), _tr('IP Address'));
    } else {
        $existing = $oIssa->getwhitelistByIp($ip_address);
        if (is_array($existing) && ($isEdit === false || $existing['ip_address'] !== $old_ip)) {
            $errors['ip_address'] = _tr("This IP address had already been defined");
        }
    }

    if (count($errors) > 0) {
        // Re-render de la misma página, sin redirigir y sin perder lo escrito.
        return newwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $action, $errors,
            array('ip_address' => $ip_address, 'note' => $note, 'id' => $old_ip));
    }

    $ok = $isEdit
        ? $oIssa->updatewhitelist($old_ip, $ip_address, $note)
        : $oIssa->savewhitelist($ip_address, $note);

    if ($ok) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'),
            $isEdit ? _tr('Update correctly') : _tr('Save correctly')));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'), $oIssa->errMsg));
    }

    return reportwhitelist($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function createFieldForm(){
    $arrFormElements = array(
        "ip_address" => array("LABEL"                  => _tr("IP Address"),
                              "REQUIRED"               => "yes",
                              "INPUT_TYPE"             => "TEXT",
                              "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "placeholder" => "192.168.1.0/24"),
                              "VALIDATION_TYPE"        => "text",
                              "VALIDATION_EXTRA_PARAM" => ""),
            "note" => array(  "LABEL"                  => _tr("Note"),
                              "REQUIRED"               => "no",
                              "INPUT_TYPE"             => "TEXT",
                              "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                              "VALIDATION_TYPE"        => "text",
                              "VALIDATION_EXTRA_PARAM" => ""),
    );
    return $arrFormElements;
}

function getAction()
{
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    if($isPost && getParameter("save_edit"))
        return "save_edit";
    else if($isPost && getParameter("save"))
        return "save";
    else if(getParameter("edit"))
        return "edit";
    else if(getParameter("new"))
        return "new";
    else if($isPost && getParameter("delete"))
        return "delete";
    else if(getParameter("action")=="view")
        return "view";
    else if(getParameter("action")=="edit")
        return "edit";
    else if(getParameter("cancel"))
        return "cancel";
    else
        return "report"; //cancel
}
?>
