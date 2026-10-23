<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.2                                               |
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
  $Id: index.php,v 1.2 2010-12-10 03:09:32 Alberto Santos asantos@palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoPortService.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $smarty->assign('SECURITY_ALERT', '');

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case 'save':
            $content = savePuerto($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'new': case 'view': case 'edit':
            $content = NewViewPuerto($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $accion);
            break;
        case 'delete':
            $content = deletePuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'cancel':
            $content = reportPuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportPuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportPuertos($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pPuertos = new paloSantoPortService($pDB);

    $field_type = getParameter("filter_type");
    $field_pattern = getParameter("filter_txt");
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->addNew("new",_tr("Define Port"));
    $oGrid->deleteList(_tr("Are you sure you wish to delete the port(s)?"),"delete",_tr("Delete"));

    $totalPuertos = $pPuertos->ObtainNumPuertos($field_type, $field_pattern);

    $limit  = 20;
    $total  = $totalPuertos;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->setTitle(_tr("Define Ports"));
    $oGrid->setIcon("modules/$module_name/images/security_define_ports.png");
    $oGrid->pagingShow(true);
    $offset = $oGrid->calculateOffset();
    $url = array(
        "menu"         =>  $module_name,
        "filter_type"  =>  $field_type,
        "filter_txt"   =>  $field_pattern
    );
    $oGrid->setURL($url);

    $arrData = null;
    $arrResult = $pPuertos->ObtainPuertos($limit, $offset, $field_type, $field_pattern);
    $arrColumns = array('',_tr("Name"),_tr("Protocol"),_tr("Details"),_tr("Option"));
    $oGrid->setColumns($arrColumns);
    if( is_array($arrResult) && $total>0 ){
        foreach($arrResult as $key => $value){
            $id = (int)$value['id'];
            $arrTmp[0] = "<input type='checkbox' name='del_x[]' value='".$id."'>";
            $arrTmp[1] = htmlspecialchars($value['name'], ENT_COMPAT, 'UTF-8');
            $arrTmp[2] = htmlspecialchars((string)$value['protocol'], ENT_COMPAT, 'UTF-8');
            if($value['protocol'] == "TCP" || $value['protocol'] == "UDP"){
                $port = $value['details'];
                $arrTmp[3] = ( stripos($port,":") === false ) ? _tr('Port')."  ".htmlspecialchars($value['details']) : _tr('Ports')."  ".htmlspecialchars($value['details']);
            }elseif($value['protocol'] == "ICMP"){
                $arr = explode(":",$value['details']);
                if(isset($arr[1]))
                    $arrTmp[3] = _tr('Type').": ".htmlspecialchars($arr[0])." "._tr('Code').": ".htmlspecialchars($arr[1]);
            }else
                $arrTmp[3] = _tr('Protocol Number').": ".htmlspecialchars($value['details']);
            $arrTmp[4] = "&nbsp;<a href='?menu=$module_name&action=view&id=".$id."'>"._tr('View')."</a>";
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);
    //begin section filter
    $arrFormFilterPuertos = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterPuertos);
    $smarty->assign("SHOW", _tr("Show"));

    $_POST["filter_type"]  = $field_type;
    $_POST["filter_txt"]   = $field_pattern;

    if(is_null($field_type) || $field_type==""){
        $nameFieldType = "";
    }else{
        $nameFieldType = $arrFormFilterPuertos["filter_type"]["INPUT_EXTRA_PARAM"][$field_type];
    }

    $oGrid->addFilterControl(_tr("Filter applied: ").$nameFieldType." = ".$field_pattern,$_POST, array("filter_type" => "name","filter_txt" => "x"));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    //end grid parameters

    return $smarty->getTemplateVars('SECURITY_ALERT').$oGrid->fetchGrid();
}

function createFieldFilter()
{
    $arrType = array("name" => _tr("Name"), "protocol" => _tr("Protocol"));

    $arrFormElements = array(
            "filter_type"  => array(   "LABEL"                  => _tr("Search"),
                                       "REQUIRED"               => "no",
                                       "INPUT_TYPE"             => "SELECT",
                                       "INPUT_EXTRA_PARAM"      => $arrType,
                                       "VALIDATION_TYPE"        => "text",
                                       "VALIDATION_EXTRA_PARAM" => ""),
            "filter_txt"   => array(   "LABEL"                  => "",
                                       "REQUIRED"               => "no",
                                       "INPUT_TYPE"             => "TEXT",
                                       "INPUT_EXTRA_PARAM"      => array("id" => "filter_value", "class" => "form-control"),
                                       "VALIDATION_TYPE"        => "text",
                                       "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}

function NewViewPuerto($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $action, $errors = array(), $data = array())
{
    $arrFormNew = createFieldFormNew();
    $protocol = isset($data['protocol']) ? $data['protocol'] : getParameter("protocol");
    if ($protocol === null || $protocol === '') $protocol = 'TCP';

    // En vista/edición, cargar la fila desde la base.
    if (($action === 'view' || $action === 'edit') && count($data) === 0) {
        $id = getParameter('id');
        if ($id === null || $id === '') $id = getParameter('idtemp');
        $oPalo = new paloSantoPortService($pDB);
        $result = $oPalo->loadPuerto($id);
        if (is_array($result)) {
            $data = array(
                'id'       => $result['id'],
                'name'     => $result['name'],
                'protocol' => $result['protocol'],
                'comment'  => $result['comment'],
            );
            if ($result['protocol'] == "TCP" || $result['protocol'] == "UDP") {
                $arrPort = explode(':', $result['details']);
                $data['port']  = $arrPort[0];
                $data['port2'] = isset($arrPort[1]) ? $arrPort[1] : '';
            } elseif ($result['protocol'] == "ICMP") {
                $value = explode(":", $result['details']);
                $data['type'] = $value[0];
                $data['code'] = isset($value[1]) ? $value[1] : '';
            } else {
                $data['protocol_number'] = $result['details'];
            }
            $protocol = $result['protocol'];
        }
    }

    // Marca de error por campo.
    foreach (array('name','port','port2','type','code','protocol_number') as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormNew[$f]['INPUT_EXTRA_PARAM']))
            $arrFormNew[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }

    $oForm = new paloForm($smarty, $arrFormNew);
    $titulo = _tr('Define Port');
    if ($action === 'edit') $titulo = _tr('Edit Port');
    if ($action === 'view') $titulo = _tr('View Port');

    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("BACK", _tr("Back"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("MODE", $action);
    $smarty->assign("PROTOCOL", $protocol);
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("ERRORS", array_merge(array(
        'name' => '', 'port' => '', 'port2' => '', 'type' => '', 'code' => '', 'protocol_number' => ''
    ), $errors));
    $smarty->assign("VALUES", array_merge(array(
        'id' => '', 'name' => '', 'port' => '', 'port2' => '', 'type' => '', 'code' => '',
        'protocol_number' => '', 'comment' => ''
    ), $data));

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", $titulo, $data);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $smarty->getTemplateVars('SECURITY_ALERT').$contenidoModulo;
}

function createFieldFormNew()
{
    $arrProtocols = array('TCP' => 'TCP', 'UDP' => 'UDP', 'ICMP' => 'ICMP', 'IP' => 'IP');

    $arrFields = array(
            "name"      => array(   "LABEL"                  => _tr("Name"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            "protocol"  => array(   "LABEL"                  => _tr("Protocol"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrProtocols,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "",
                                    "ONCHANGE"               => "hideField(this.value)"),
            "port"      => array(   "LABEL"                  => _tr("Port"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "numeric",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            "port2"     => array(   "LABEL"                  => _tr("To"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "numeric",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            "type"     => array(    "LABEL"                  => _tr("Type"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "numeric",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            "code"     => array(    "LABEL"                  => _tr("Code"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "numeric",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
    "protocol_number"  => array(    "LABEL"                  => _tr("Protocol Number"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "numeric",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            "comment"   => array(   "LABEL"                  => _tr("Comment"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXTAREA",
                                    "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "" ),
            );

    return $arrFields;
}

/**
 * Valida un puerto TCP/UDP (1-65535).
 */
function validPortNumber($value)
{
    return preg_match('/^[0-9]{1,5}$/', (string)$value) && (int)$value >= 1 && (int)$value <= 65535;
}

function savePuerto($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $mode       = getParameter('mode');
    if (!in_array($mode, array('new','edit'), true)) $mode = 'new';
    $name       = trim((string)getParameter("name"));
    $protocol   = getParameter("protocol");
    if (!in_array($protocol, array('TCP','UDP','ICMP','IP'), true)) $protocol = 'TCP';
    $port1      = trim((string)getParameter("port"));
    $port2      = trim((string)getParameter("port2"));
    $type       = trim((string)getParameter("type"));
    $code       = trim((string)getParameter("code"));
    $protocol_number = trim((string)getParameter("protocol_number"));
    $comment    = (string)getParameter("comment");
    $id_except  = ( $mode == 'new' ) ? 0 : getParameter('idtemp');

    $errors = array();
    if ($name === '') $errors['name'] = sprintf(_tr('%s is required'), _tr('Name'));

    if ($protocol == 'TCP' || $protocol == 'UDP') {
        if ($port1 === '') {
            $errors['port'] = sprintf(_tr('%s is required'), _tr('Port'));
        } elseif (!validPortNumber($port1)) {
            $errors['port'] = sprintf(_tr('%s is invalid'), _tr('Port'));
        }
        if ($port2 !== '' && !validPortNumber($port2)) {
            $errors['port2'] = sprintf(_tr('%s is invalid'), _tr('To'));
        } elseif ($port2 !== '' && $port1 !== '' && (int)$port2 < (int)$port1) {
            $errors['port2'] = sprintf(_tr('%s is invalid'), _tr('To'));
        }
    } elseif ($protocol == 'ICMP') {
        if ($type === '') $errors['type'] = sprintf(_tr('%s is required'), _tr('Type'));
        elseif (!preg_match('/^[0-9]{1,3}$/', $type) || (int)$type > 255) $errors['type'] = sprintf(_tr('%s is invalid'), _tr('Type'));
        if ($code === '') $errors['code'] = sprintf(_tr('%s is required'), _tr('Code'));
        elseif (!preg_match('/^[0-9]{1,3}$/', $code) || (int)$code > 255) $errors['code'] = sprintf(_tr('%s is invalid'), _tr('Code'));
    } elseif ($protocol == 'IP') {
        if ($protocol_number === '') $errors['protocol_number'] = sprintf(_tr('%s is required'), _tr('Protocol Number'));
        elseif (!preg_match('/^[0-9]{1,3}$/', $protocol_number) || (int)$protocol_number > 255) $errors['protocol_number'] = sprintf(_tr('%s is invalid'), _tr('Protocol Number'));
    }

    $port = ($port2 == "") ? $port1 : $port1.":".$port2;
    $oPalo = new paloSantoPortService($pDB);
    $portName = "";
    if (count($errors) === 0 &&
        $oPalo->hasPuerto($protocol, $port, $type, $code, $protocol_number, $portName, $id_except) == true) {
        $errors['port'] = _tr("This port had already been defined").": ".$portName;
    }

    if (count($errors) > 0) {
        $data = array(
            'name' => $name, 'protocol' => $protocol, 'port' => $port1, 'port2' => $port2,
            'type' => $type, 'code' => $code, 'protocol_number' => $protocol_number,
            'comment' => $comment, 'id' => $id_except,
        );
        return NewViewPuerto($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $mode, $errors, $data);
    }

    if($mode == 'new'){
        if($oPalo->savePuertos($name, $protocol, $port, $type, $code, $protocol_number, $comment)){
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'), _tr('Save correctly')));
        }
        else{
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'), $oPalo->errMsg));
            return NewViewPuerto($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $mode);
        }
    }
    else{//edit
        $desactivated = false;
        if($oPalo->updatePuertos($id_except, $name, $protocol, $port, $type, $code, $protocol_number, $comment, $desactivated)){
            $msg = _tr("Update correctly");
            if($desactivated)
                $msg .= " "._tr("Some rules could be deactivated due to these changes, please check the firewall rules");
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'), $msg));
        }
        else{
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'), $oPalo->errMsg));
            return NewViewPuerto($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $mode);
        }
    }
    return reportPuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function deletePuertos($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $oPalo = new paloSantoPortService($pDB);
    $str_msj_error = "";
    $ids = isset($_POST['del_x']) && is_array($_POST['del_x']) ? $_POST['del_x'] : array();

    if (count($ids) === 0) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('warning', _tr('Message'), _tr('No records were selected')));
        return reportPuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    foreach( $ids as $id ){
        $id = (int)$id;
        $port = "";
        if(!$oPalo->isPortInService($id, $port)){
            if( $oPalo->deletePuerto($id) == false )
                $str_msj_error .= htmlspecialchars((string)$oPalo->errMsg, ENT_COMPAT, 'UTF-8')."<br />";
        } else {
            $info = $oPalo->loadPuerto($id);
            $portName = is_array($info) && isset($info['name']) ? $info['name'] : '';
            $str_msj_error .= _tr("Port used in a firewall rule").": ".htmlspecialchars($portName, ENT_COMPAT, 'UTF-8').". "
                             . _tr("You have to delete the rule related in order to delete this port")."<br />";
        }
    }
    if( strlen($str_msj_error) == 0 ){
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'), _tr('Delete correctly')));
    }
    else{
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'), $str_msj_error, array('html' => true)));
    }

    return reportPuertos($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}


function getAction()
{
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if($isPost && getParameter("delete")) //Get parameter by POST (submit)
        return "delete";
    else if(getParameter("new"))
        return "new";
    else if($isPost && getParameter("save"))
        return "save";
    else if(getParameter("edit"))
        return "edit";
    else if(getParameter("cancel"))
        return "cancel";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else if(getParameter("action")=="view") //Get parameter by GET (command pattern, links)
        return "view";
    else
        return "report";
}
?>
