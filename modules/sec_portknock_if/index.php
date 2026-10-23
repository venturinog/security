<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.4-2                                               |
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
  $Id: index.php,v 1.1 2008-09-11 03:09:47 Alex Villacis Lasso <a_villacis@palosanto.com> Exp $ */

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";
require_once "libs/paloSantoNetwork.class.php";

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/paloSantoPortKnockInterfaces.class.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pDB = new paloDB($arrConf['dsn_conn_database']);

    switch (getParameter('action')) {
    case 'setport':
        return setPortKnockPort($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    case 'viewauths':
        return listPortKnockCurrentAuths($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    default:
        return listPortKnockInterfaces($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
}

function listPortKnockInterfaces(&$smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pk = new paloSantoPortKnockInterfaces($pDB);
    
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr('PortKnock Protected Interfaces'));
    $oGrid->setColumns(array('', _tr('Interface'), _tr('Protection'), _tr('Port to Knock'), _tr('Authorizations'), _tr('Options')));
    $oGrid->deleteList(
        _tr('Are you sure you wish to unprotect the interface?'),
        'unprotect', _tr('Unprotect interface'));

    $listaIf = PaloNetwork::obtener_interfases_red_fisicas();
    $recordset = $pk->listProtectedInterfaces();

    // Eliminar la protección de una interfaz específica
    if (isset($_POST['unprotect']) && isset($_POST['eth_in']) && 
        isset($recordset[$_POST['eth_in']])) {
    	$bExito = $pk->removeProtectedInterface($_POST['eth_in']);
        if (!$bExito) {
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pk->errMsg));
        } else {
            $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Update correctly")));
        }
        $recordset = $pk->listProtectedInterfaces();
    }

    $data = array();
    $smarty->assign('SECURITY_ALERT', $smarty->getTemplateVars('SECURITY_ALERT'));
    if (is_array($recordset)) {
        foreach (array_keys($listaIf) as $eth_in) {
        	if (!isset($recordset[$eth_in])) {
        		$recordset[$eth_in] = array(
                    'eth_in'    => $eth_in,
                    'udp_port'  => NULL,
                    'num_auth'  => 0,
                );
        	}
        }
        ksort($recordset);
    	foreach ($recordset as $eth_in => $eth_info) {
            $eth = htmlspecialchars($eth_in, ENT_COMPAT, 'UTF-8');
    		$data[] = array(
                is_null($eth_info['udp_port']) ? '' : '<input type="radio" name="eth_in" value="'.$eth.'" />',
                $eth,
                is_null($eth_info['udp_port']) ? _tr('Inactive') : _tr('Active'),
                "<a href=\"?menu=$module_name&amp;action=setport&amp;eth_in=".urlencode($eth_in)."\">".(is_null($eth_info['udp_port']) ? '['._tr('Enable Protection').']' : (int)$eth_info['udp_port']).'</a>',
                ($eth_info['num_auth'] > 0) ? (int)$eth_info['num_auth'] : '-',
                ($eth_info['num_auth'] > 0) ? "<a href=\"?menu=$module_name&amp;action=viewauths&amp;eth_in=".urlencode($eth_in)."\">["._tr('View authorizations').']</a>': '-',
            );
    	}
    }

    $oGrid->pagingShow(false);
    $url = array(
        "menu"         =>  $module_name,
    );    
    $oGrid->setURL($url);
    $oGrid->setData($data);
    return $smarty->getTemplateVars('SECURITY_ALERT').$oGrid->fetchGrid();
}

function setPortKnockPort(&$smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    if (isset($_POST['cancel'])) {
        Header("Location: ?menu=$module_name");
        return NULL;
    }

    $listaIf = PaloNetwork::obtener_interfases_red_fisicas();
    $cbo_eth = array();
    foreach ($listaIf as $eth_in => $eth_info) {
        $cbo_eth[$eth_in] = $eth_info['Name'].' - '.$eth_info['Inet Addr'];
    }

    $requested_eth_in = getParameter('eth_in');
    if (!is_null($requested_eth_in) && !isset($cbo_eth[$requested_eth_in]))
        $requested_eth_in = NULL;
    $_POST['eth_in'] = $requested_eth_in;

    $pk = new paloSantoPortKnockInterfaces($pDB);
    $recordset = $pk->listProtectedInterfaces();
    if (!isset($_POST['port'])) {
        if (!is_null($requested_eth_in) && isset($recordset[$requested_eth_in])) {
            $_POST['port'] = $recordset[$requested_eth_in]['udp_port'];
        } else {
            // Generar un número aleatorio entre 32768 y 65535
            $_POST['port'] = rand(32768, 65535);
        }
    }

    $errors = array();
    if (isset($_POST['save'])) {
        $port = trim((string)getParameter('port'));
        if ($requested_eth_in === NULL) {
            $errors['eth_in'] = sprintf(_tr('%s is required'), _tr('Interface'));
        } elseif (!in_array($_POST['eth_in'], array_keys($listaIf))) {
            $errors['eth_in'] = _tr('Invalid interface');
        }
        if ($port === '') {
            $errors['port'] = sprintf(_tr('%s is required'), _tr('Port'));
        } elseif (!preg_match('/^[0-9]{1,5}$/', $port) || (int)$port < 1 || (int)$port > 65535) {
            $errors['port'] = _tr('Port must be in range 0-65535');
        }

        if (count($errors) === 0) {
            $bExito = $pk->setProtectedInterfacePort($_POST['eth_in'], $port);
            if (!$bExito) {
                $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pk->errMsg));
            } else {
                Header("Location: ?menu=$module_name");
                return NULL;
            }
        } else {
            $_POST['port'] = $port;
        }
    }

    $camposFormulario = array(
        "eth_in"    =>  array(
            "LABEL"                 => _tr("Interface"),
            "REQUIRED"              => "yes",
            "INPUT_TYPE"            => "SELECT",
            "INPUT_EXTRA_PARAM"     => $cbo_eth,
            "VALIDATION_TYPE"       => "text",
            "VALIDATION_EXTRA_PARAM"=> ""
        ),
        "port"      => array(
            "LABEL"                  => _tr("Port"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
    );
    foreach (array('eth_in','port') as $f) {
        if (!isset($errors[$f])) continue;
        if ($camposFormulario[$f]['INPUT_TYPE'] === 'SELECT') {
            $camposFormulario[$f]['CLASS'] = 'is-invalid';
            continue;
        }
        if (!isset($camposFormulario[$f]['INPUT_EXTRA_PARAM']) || !is_array($camposFormulario[$f]['INPUT_EXTRA_PARAM']))
            $camposFormulario[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($camposFormulario[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $camposFormulario[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $camposFormulario[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }
    $oForm = new paloForm($smarty, $camposFormulario);
    $smarty->assign(array(
        'MODULE_NAME'   =>  $module_name,
        'CANCEL'        =>  _tr('Cancel'),
        'SAVE'          =>  _tr('Save'),
        'REQUIRED_FIELD'=>  _tr('Required field'),
        'ERRORS'        => array_merge(array('eth_in' => '', 'port' => ''), $errors),
        'icon'          =>  'images/list.png',
    ));

    return $smarty->getTemplateVars('SECURITY_ALERT')
        . $oForm->fetchForm("$local_templates_dir/form.tpl", _tr('Assign Knocking Port'), $_POST);
}

function listPortKnockCurrentAuths(&$smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $eth_in = getParameter('eth_in');
    if (is_null($eth_in)) {
        Header("Location: ?menu=$module_name");
        return NULL;
    }

    include_once "libs/paloSantoACL.class.php";

    $pACL = new paloACL($arrConf['issabel_dsn']['acl']);
    $pk = new paloSantoPortKnockInterfaces($pDB);
    
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr('PortKnock Interface Authorizations'));
    $oGrid->setColumns(array('', _tr('IP'), _tr('User'), _tr('Ports'), _tr('Since')));
    $oGrid->deleteList(
        _tr('Are you sure you wish to revoke this authorization?'),
        'delete', _tr('Revoke authorization'));
    
    if (isset($_POST['delete']) && isset($_POST['id_user_ip'])) {
    	$l = explode('-', $_POST['id_user_ip']);
        if (count($l) >= 2) {
        	$bExito = $pk->removeAuthorizationsUserInterface($l[0], $l[1]);
            if (!$bExito) {
                $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pk->errMsg));
            } else {
                $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("Revocation successful")));
                Header("Location: ?menu=$module_name");
                return NULL;
            }
        }
    }

    $recordset = $pk->listAuthorizationsInterface($eth_in);

    $data = array();
    if (is_array($recordset)) {
    	foreach ($recordset as $id_user => $auth_user) {
            $userinfo = $pACL->getUsers($id_user);
    		foreach ($auth_user as $ip_source => $auth_ips) {
    			$listaProto = array();
                $ruleStart = NULL;
                foreach ($auth_ips as $id_auth => $info_auth) {
    				$listaProto[] = $info_auth['name'];
                    $ruleStart = $info_auth['rule_start'];
    			}
                $value = htmlspecialchars($id_user.'-'.$ip_source, ENT_COMPAT, 'UTF-8');
                $data[] = array(
                    '<input type="radio" name="id_user_ip" value="'.$value.'" />',
                    htmlspecialchars($ip_source, ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars(isset($userinfo[0][1]) ? $userinfo[0][1] : '', ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars(implode(' ', $listaProto), ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars((string)$ruleStart, ENT_COMPAT, 'UTF-8'),
                );
    		}
    	}
    }
	
    $oGrid->pagingShow(false);
    $url = array(
        "menu"         =>  $module_name,
    );    
    $oGrid->setURL($url);
    $oGrid->setData($data);
    return $smarty->getTemplateVars('SECURITY_ALERT').$oGrid->fetchGrid();
}
?>