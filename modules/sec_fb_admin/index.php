<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel Version 4.0.0                                                |
  +----------------------------------------------------------------------+
  | Copyright (c) 2017 Issabel Foundation                                |
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
*/
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/IssabelF2Bservice.class.php";
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
            $content = saveJail($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'new': case 'view': case 'edit':
            $content = NewViewZone($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $accion);
            break;
        case 'cancel':
            $content = reportJails($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'start':
            exec('/usr/bin/issabel-helper fb_client start', $respuesta, $retorno);
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Fail2ban has been activated")));
            $content = reportJails($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case 'stop':
            exec('/usr/bin/issabel-helper fb_client stop', $respuesta, $retorno);
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Fail2ban has been deactivated")));
            $content = reportJails($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportJails($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportJails($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $iJails = new IssabelF2BService($pDB);

    $iJails->creaTablaSiNoExiste();

    $field_type = null;
    $field_pattern = null;
    $oGrid  = new paloSantoGrid($smarty);

    $totalJails = $iJails->obtainNumJails();

    $limit  = 20;
    $total  = $totalJails;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->setTitle(_tr("Jails"));
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
    $arrResult = $iJails->obtainJails($limit, $offset);
    $arrColumns = array(_tr("Name"),_tr("Count Failed Attempts"),_tr("Ban Time (hours)"),_tr("Whitelist"),_tr("Enabled"),_tr("Options"));
    $oGrid->setColumns($arrColumns);
    if( is_array($arrResult) && $total>0 ){
        foreach($arrResult as $key => $value){
            $id = (int)$value['id'];
            $arrTmp[0] = htmlspecialchars($value['name'], ENT_COMPAT, 'UTF-8');
            $arrTmp[1] = htmlspecialchars((string)$value['maxretry'], ENT_COMPAT, 'UTF-8');
            $arrTmp[2] = htmlspecialchars((string)$value['bantime'], ENT_COMPAT, 'UTF-8');
            $arrTmp[3] = htmlspecialchars((string)$value['ignoreip'], ENT_COMPAT, 'UTF-8');
            $arrTmp[4] = ($value['enabled'] ? _tr('Yes') : _tr('No'));
            $arrTmp[5] = "&nbsp;<a href='?menu=$module_name&action=view&id=".$id."'>"._tr('View')."</a>";
            $arrData[] = $arrTmp;
        }
    }

    if($iJails->isactive()) {
        // if systemctl status is off (1) then offer option to enable
        $oGrid->customAction("start",_tr("Enable fail2ban"));
    } else {
        $oGrid->customAction("stop",_tr("Disable fail2ban"));
    }

    $oGrid->setData($arrData);

    // La grilla ya emite su propio <form> con el token CSRF inyectado por
    // paloSantoGrid; no hay que envolverla otra vez.
    return $smarty->getTemplateVars('SECURITY_ALERT').$oGrid->fetchGrid();
}

function NewViewZone($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $action, $errors = array(), $data = array())
{
    $arrFormNew = createFieldFormNew();
    $iJails = new IssabelF2BService($pDB);

    if (($action === 'view' || $action === 'edit') && count($data) === 0) {
        $id = getParameter('id');
        if ($id === null || $id === '') $id = getParameter('idtemp');
        $result = $iJails->loadJail($id);
        if (is_array($result)) {
            $data = array(
                'id'        => $result['id'],
                'name'      => $result['name'],
                'maxretry'  => $result['maxretry'],
                'bantime'   => $result['bantime'],
                'ignoreip'  => $result['ignoreip'],
                'enabled'   => $result['enabled'] == '1' ? 'on' : 'off',
            );
        }
    }

    foreach (array('maxretry','bantime','ignoreip') as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormNew[$f]['INPUT_EXTRA_PARAM']))
            $arrFormNew[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormNew[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }

    $oForm = new paloForm($smarty, $arrFormNew);

    $titulo = '';
    if( $action == 'edit' ) $titulo = _tr('Edit Jail');
    if( $action == 'view' ) $titulo = _tr('View Jail');
    if( $action == 'new' )  $titulo = _tr('New Jail');

    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("BACK", _tr("Back"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");
    $smarty->assign("SAVE", _tr('Save'));
    $smarty->assign("YES", _tr('Yes'));
    $smarty->assign("NO", _tr('No'));
    $smarty->assign("MODE", $action);
    $smarty->assign("ERRORS", array_merge(array('maxretry' => '', 'bantime' => '', 'ignoreip' => ''), $errors));
    $smarty->assign("VALUES", array_merge(array(
        'id' => '', 'name' => '', 'maxretry' => '', 'bantime' => '', 'ignoreip' => '', 'enabled' => 'off'
    ), $data));

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", $titulo, $data);
    $contenidoModulo = "<form method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $smarty->getTemplateVars('SECURITY_ALERT').$contenidoModulo;
}

function createFieldFormNew() {

    $arrFields = array(
        "name" => array(
            "LABEL"                  => _tr("Name"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "readonly" => "readonly"),
            "EDITABLE"               => "no",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "maxretry" => array(
            "LABEL"                  => _tr("Count Failed Attempts"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
            "EDITABLE"               => "yes",
            "VALIDATION_TYPE"        => "numeric",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "bantime" => array(
            "LABEL"                  => _tr("Ban Time (hours)"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
            "EDITABLE"               => "yes",
            "VALIDATION_TYPE"        => "numeric",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "ignoreip" => array(
            "LABEL"                  => _tr("Whitelist"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXTAREA",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "id" => "ignoreipfield", "placeholder" => "127.0.0.1 192.168.0.0/24"),
            "VALIDATION_TYPE"        => "",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "enabled"    => array(
            "LABEL"                  => _tr("Enabled"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "CHECKBOX",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        )
    );
    return $arrFields;
}

/**
 * Valida una lista de IPs/CIDR separadas por espacios o comas.
 */
function validIpList($value)
{
    $value = trim((string)$value);
    if ($value === '') return true;
    foreach (preg_split('/[\s,]+/', $value) as $item) {
        if ($item === '') continue;
        if (strpos($item, '/') !== false) {
            list($ip, $mask) = explode('/', $item, 2);
            $max = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;
            if (!filter_var($ip, FILTER_VALIDATE_IP) || !preg_match('/^[0-9]{1,3}$/', $mask) || (int)$mask > $max)
                return false;
        } elseif (!filter_var($item, FILTER_VALIDATE_IP)) {
            return false;
        }
    }
    return true;
}

function saveJail($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $arrFormNew = createFieldFormNew();
    $mode       = getParameter("mode");
    $maxretry   = trim((string)getParameter("maxretry"));
    $bantime    = trim((string)getParameter("bantime"));
    $ignoreip   = trim((string)getParameter("ignoreip"));
    $enabled    = getParameter("enabled");

    $id_except  = getParameter('idtemp');

    // Be sure ignoreip in fail2ban is separated with spaces (and one space) only
    $ignoreip = str_replace(","," ",$ignoreip);
    $ignoreip = preg_replace('/\s+/', ' ', $ignoreip);

    $errors = array();
    if ($maxretry === '' || !preg_match('/^[0-9]+$/', $maxretry) || (int)$maxretry < 1)
        $errors['maxretry'] = sprintf(_tr('%s is invalid'), _tr('Count Failed Attempts'));
    if ($bantime === '' || !preg_match('/^[0-9]+$/', $bantime) || (int)$bantime < 1)
        $errors['bantime'] = sprintf(_tr('%s is invalid'), _tr('Ban Time (hours)'));
    if (!validIpList($ignoreip))
        $errors['ignoreip'] = sprintf(_tr('%s is invalid'), _tr('Whitelist'));

    $enabled = ($enabled == 'on' || $enabled == 'On' || $enabled == 'ON') ? '1' : '0';

    if (count($errors) > 0) {
        return NewViewZone($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $mode, $errors, array(
            'id' => $id_except, 'name' => getParameter('name'), 'maxretry' => $maxretry,
            'bantime' => $bantime, 'ignoreip' => $ignoreip,
            'enabled' => $enabled == '1' ? 'on' : 'off',
        ));
    }

    $iJails = new IssabelF2BService($pDB);
    if($iJails->updateJail($id_except, $maxretry, $bantime, $ignoreip, $enabled)) {
        $iJails->F2BUpdateJails($pDB);
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Update correctly")));
    }
    else{
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $iJails->errMsg));
        return NewViewZone($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $mode);
    }

    return reportJails($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function getAction()
{
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    else if($isPost && getParameter("save"))
        return "save";
    else if(getParameter("edit"))
        return "edit";
    else if(getParameter("cancel"))
        return "cancel";
    else if(getParameter("action")=="show")
        return "show";
    else if(getParameter("action")=="view")
        return "view";
    // start/stop cambian estado: sólo se aceptan por POST (botón de la grilla).
    else if($_SERVER['REQUEST_METHOD'] === 'POST' && getParameter("start"))
        return "start";
    else if($_SERVER['REQUEST_METHOD'] === 'POST' && getParameter("stop"))
        return "stop";
    else if(getParameter("start") || getParameter("stop"))
        return "report";
    else
        return "report";
}

?>
