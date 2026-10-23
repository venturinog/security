<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.4-5                                               |
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
  $Id: index.php,v 1.1 2011-05-13 11:05:31 Estefanía Morán Meza emoran@palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoJSON.class.php";
require_once("libs/misc.lib.php");

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoChangePassword.class.php";

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
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $smarty->assign('SECURITY_ALERT', '');

    //actions
    $action = getAction();
    $content = "";
    switch($action){
        case "update_advanced_security_settings":
            $content = updateAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "edit":
            $content = viewFormAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $arrConf, 'edit');
            break;
        case "cancel":
        default: // view
            $content = viewFormAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $arrConf, 'view');
            break;
    }
    return $content;
}

function viewFormAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $arrConf, $mode = 'view', $errors = array())
{
    $pAdvancedSecuritySettings       = new paloSantoAdvancedSecuritySettings($arrConf);
    $value_ipbx_frontend             = $pAdvancedSecuritySettings->isActivatedIssabelPBXFrontend();
    $value_anonymous_sip             = $pAdvancedSecuritySettings->isActivatedAnonymousSIP();
    $arrFormAdvancedSecuritySettings = createFieldForm();

    // Recuadro rojo por campo (conservando form-control).
    foreach (array('ipbx_password','ipbx_confirm_passwrod') as $f) {
        if (!isset($errors[$f]) || $errors[$f] === '') continue;
        if (!isset($arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM']))
            $arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormAdvancedSecuritySettings[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
    $oForm = new paloForm($smarty,$arrFormAdvancedSecuritySettings);

    if ($mode !== 'edit') $mode = 'view';

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("subtittle1", _tr("Enable access"));
    $smarty->assign("subtittle2", _tr("Change Password"));
    $smarty->assign("value_ipbx_frontend", $value_ipbx_frontend);
    $smarty->assign("value_anonymous_sip", $value_anonymous_sip);
    $smarty->assign("MODE", $mode);
    $smarty->assign("STATUS_IPBX", $value_ipbx_frontend ? _tr('Enabled') : _tr('Disabled'));
    $smarty->assign("STATUS_SIP", $value_anonymous_sip ? _tr('Enabled') : _tr('Disabled'));
    $smarty->assign("PASSWORD_MASK", '••••••••');
    $smarty->assign("ERRORS", array_merge(array('ipbx_password' => '', 'ipbx_confirm_passwrod' => ''), $errors));
    $smarty->assign("icon", "modules/".$module_name."/images/security_advanced_settings.png");

    // Las contraseñas no se reenvían al HTML (ni siquiera en el re-render de
    // error): paloForm las volvería a imprimir en el value del input.
    $_POST['ipbx_password'] = '';
    $_POST['ipbx_confirm_passwrod'] = '';
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Advanced Security Settings"), $_POST);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $smarty->getTemplateVars('SECURITY_ALERT').$content;
}

function updateAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $arrFormAdvancedSecuritySettings = createFieldForm();

    $ipbx_password         = isset($_POST["ipbx_password"]) ? (string)getParameter("ipbx_password") : '';
    $ipbx_confirm_passwrod = isset($_POST["ipbx_confirm_passwrod"]) ? (string)getParameter("ipbx_confirm_passwrod") : '';
    $status_ipbx_frontend  = getParameter("status_ipbx_frontend");
    $status_anonymous_sip  = getParameter("status_anonymous_sip");

    $errors = array();
    $changePassword = ($ipbx_password !== '' || $ipbx_confirm_passwrod !== '');
    if ($changePassword) {
        if ($ipbx_password === '')
            $errors['ipbx_password'] = sprintf(_tr('%s is required'), _tr("Database and Web Administration IssabelPBX Password"));
        elseif (!validatePassword($ipbx_password))
            $errors['ipbx_password'] = _tr("Password may only contain alphanumeric characters, spaces, or the following: .&-@=_!<>.");
        if ($ipbx_confirm_passwrod === '')
            $errors['ipbx_confirm_passwrod'] = sprintf(_tr('%s is required'), _tr("Password Confirmation"));
        elseif (!validateConfirmationPassword($ipbx_password, $ipbx_confirm_passwrod))
            $errors['ipbx_confirm_passwrod'] = _tr("Password and confirmation do not match!");
    }

    if (count($errors) > 0) {
        return viewFormAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $arrConf, 'edit', $errors);
    }

    $pAdvancedSecuritySettings = new paloSantoAdvancedSecuritySettings($arrConf,$pDB);

    // Los interruptores se guardan con el mismo POST, no por AJAX.
    $okSwitch1 = $pAdvancedSecuritySettings->updateStatusIssabelPBXFrontend($status_ipbx_frontend ? '1' : '0');
    $okSwitch2 = $pAdvancedSecuritySettings->updateStatusAnonymousSIP($status_anonymous_sip ? '1' : '0');

    $message = '';
    $ok = ($okSwitch1 && $okSwitch2);

    if ($changePassword) {
        $resultChangePass = $pAdvancedSecuritySettings->changeIssabelPBXPassword($ipbx_password, $arrConf);
        $okPass = is_array($resultChangePass) && isset($resultChangePass['result']) && $resultChangePass['result'];
        if ($okPass) {
            $message = _tr("Password has been updated.");
            $_POST["ipbx_password"] = "";
            $_POST["ipbx_confirm_passwrod"] = "";
        } else {
            $ok = false;
            $message = _tr("Password has not been updated.");
            if (is_array($resultChangePass) && isset($resultChangePass['arrUpdateFiles']) && count($resultChangePass['arrUpdateFiles']) > 0) {
                $message .= ' '._tr("But the following files have been modified:").' '
                          . implode(', ', $resultChangePass['arrUpdateFiles']);
            }
        }
    }

    if ($ok) {
        if ($message === '') $message = _tr("Update correctly");
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Information"), $message));
    } else {
        if ($message === '') $message = _tr("The settings could not be saved");
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $message));
    }

    return viewFormAdvancedSecuritySettings($smarty, $module_name, $local_templates_dir, $arrConf, 'view');
}

function validatePassword($ipbx_password){
    $patron = '/^[a-zA-Z0-9-@=_!<> .|&]+$/';
    $result = preg_match($patron, $ipbx_password);
    if(trim($ipbx_password) != '' && $result)
        return true;
    return false;
}

function validateConfirmationPassword($ipbx_password, $ipbx_confirm_passwrod){
    if($ipbx_password == $ipbx_confirm_passwrod)
      return true;
    return false;
}

function createFieldForm()
{
    $arrFields = array(
         "status_ipbx_frontend"    => array ("LABEL"                  => _tr('Enable direct access (Non-embedded) to IssabelPBX'),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                           ),
         "status_anonymous_sip"    => array ("LABEL"                  => _tr('Enable anonymous SIP calls'),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                           ),
            "ipbx_password"        => array ("LABEL"                  => _tr("Database and Web Administration IssabelPBX Password"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "autocomplete" => "new-password"),
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                           ),
            "ipbx_confirm_passwrod" => array ("LABEL"                 => _tr("Password Confirmation"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "PASSWORD",
                                            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "autocomplete" => "new-password"),
                                            "VALIDATION_TYPE"        => "",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                           )
            );
    return $arrFields;
}

function getAction()
{
    if($_SERVER['REQUEST_METHOD'] === 'POST' && getParameter("update_advanced_security_settings"))
        return "update_advanced_security_settings";
    if(getParameter("edit") || getParameter("action")=="edit")
        return "edit";
    if(getParameter("cancel"))
        return "cancel";
    return "view_form";
}
?>
