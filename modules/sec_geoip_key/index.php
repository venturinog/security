<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4                                                    |
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
  | The Initial Developer of the Original Code is Issabel Foundation     |
  +----------------------------------------------------------------------+
  $Id: index.php, Thu 13 May 2021 06:21:48 PM EDT, German Venturino german@issabel.com
*/
//include issabel framework
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $smarty->assign('SECURITY_ALERT', '');

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "save_new":
            $content = saveNewGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf);
            break;
        case "edit":
        case "view":
        default:
            $content = viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, $action);
            break;
    }
    return $content;
}

/**
 * Devuelve la clave guardada, si hay una. No se imprime nunca en la página.
 */
function getSavedGeoIPKey()
{
    $respuesta = array();
    exec('/usr/bin/issabel-helper manage_geoip_key get', $respuesta, $retorno);
    return trim(implode(' ', $respuesta));
}

function viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, $action = 'view', $errors = array(), $enviado = array())
{
    if ($action !== 'edit') $action = 'view';

    $saved_key = getSavedGeoIPKey();
    $arrFormGeoIP_KEY = createFieldForm();

    if (isset($errors['key']) && $errors['key'] !== '') {
        if (!isset($arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM']) || !is_array($arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM']))
            $arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormGeoIP_KEY['key']['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
    $oForm = new paloForm($smarty, $arrFormGeoIP_KEY);

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("BACK", _tr("Back"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("MAXMIND_SIGNUP", _tr("Create a MaxMind account"));
    $smarty->assign("MODE", $action);
    $smarty->assign("HAS_KEY", $saved_key !== '' ? 'yes' : 'no');
    $smarty->assign("KEY_STATUS", $saved_key !== '' ? _tr("Configured") : _tr("Not configured"));
    $smarty->assign("MASKED_KEY", $saved_key !== '' ? '••••••••' : '');
    $smarty->assign("ERRORS", array_merge(array('key' => ''), $errors));
    $smarty->assign("enviado", array_merge(array('key' => ''), $enviado));
    $smarty->assign("icon", "images/list.png");

    $data = ($action === 'edit') ? array('key' => '') : array('key' => '');
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("GeoIP KEY"), $data);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $smarty->getTemplateVars('SECURITY_ALERT').$content;
}

function saveNewGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf)
{
    $key = isset($_POST['key']) ? trim((string)$_POST['key']) : '';

    // Campo vacío = conservar la clave actual (no se reenvía el secreto en el
    // HTML ni se borra por accidente).
    if ($key === '') {
        if (getSavedGeoIPKey() === '') {
            return viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, 'edit',
                array('key' => sprintf(_tr('%s is required'), _tr('key'))));
        }
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('info', _tr("Information"), _tr("The key has not changed")));
        return viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, 'view');
    }

    $errors = array();
    if (!preg_match('/^[A-Za-z0-9_-]{1,256}$/', $key)) {
        $errors['key'] = sprintf(_tr('%s is invalid'), _tr("key"));
    }

    if (count($errors) > 0) {
        return viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, 'edit', $errors);
    }

    $respuesta = array();
    exec('/usr/bin/issabel-helper manage_geoip_key save '.escapeshellarg($key), $respuesta, $retorno);
    if ($retorno == 0) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("updated")));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), _tr("The key could not be saved")));
    }
    return viewFormGeoIP_KEY($smarty, $module_name, $local_templates_dir, $arrConf, 'view');
}

function createFieldForm()
{
    $arrFields = array(
        "key"   => array( "LABEL"                  => _tr("key"),
                          "REQUIRED"               => "no",
                          "INPUT_TYPE"             => "PASSWORD",
                          "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "autocomplete" => "off", "placeholder" => _tr("Enter a new key")),
                          "VALIDATION_TYPE"        => "text",
                          "VALIDATION_EXTRA_PARAM" => ""
                        ),
    );
    return $arrFields;
}

function getAction()
{
    if($_SERVER['REQUEST_METHOD'] === 'POST' && getParameter("save_new")) return "save_new";
    else if(getParameter("edit")) return "edit";
    else if(getParameter("action")=="edit") return "edit";
    else if(getParameter("cancel")) return "view";
    else return "view";
}
?>
