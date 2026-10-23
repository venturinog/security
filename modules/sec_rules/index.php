<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2017 Issabel Foundation                                |
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
  $Id: index.php, Thu 13 May 2021 06:19:04 PM EDT, nicolas@issabel.com
*/
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "modules/sec_ports/libs/paloSantoPortService.class.php";
require_once "libs/paloSantoNetwork.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoRules.class.php";

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
    $accion = getAction();

    switch($accion){
        case "new":
            $content = constructForm($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, array());
            break;
        case "edit":
            $content = editRulePage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "desactivatefirewall":
            $content = desactivateFirewall($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "activatefirewall":
            $content = activateFirewall($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "save":
            $content = saveRulePage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "delete":
            $content = deleteRules($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "activate":
        case "deactivate":
            $content = toggleRule($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $accion);
            break;
        case "reorder":
            $content = reorderRules($pDB);
            break;
        case "discardchanges":
            $content = discardChanges($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "move_up":
        case "move_down":
            $content = moveRule($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $accion);
            break;
        case "cancel":
        default:
            $content = showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

/**
 * Página principal: avisos de estado + grilla de reglas + grilla GeoIP.
 */
function showRulesPage($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRules = new paloSantoRules($pDB);

    $alert = (string)$smarty->getTemplateVars('SECURITY_ALERT');
    if ($pRules->isFirstTime()) {
        $alert .= issabel_alert_html('warning', _tr("Firewall"),
            _tr("The firewall has not been activated yet"));
    } elseif (!$pRules->isExecutedInSystem()) {
        $alert .= issabel_alert_html('warning', _tr("Firewall"),
            _tr("There are changes pending to apply to the firewall"));
    }

    if ($pRules->ObtainNumRulesGEOIP() > 0 && !$pRules->hasGeoip()) {
        $alert .= issabel_alert_html('error', _tr("GeoIP"),
            _tr("GeoIP rules are configured but the geoip match is not available. Install xtables-addons and generate /usr/share/xt_geoip, then apply the rules again."));
    }

    // `paloSantoGrid::setTitle()` no se imprime en la plantilla Modern
    // (_list.tpl), así que las secciones llevan su propio encabezado. La
    // grilla GeoIP va primero, como en la interfaz histórica.
    return $alert
        . renderToolbar($smarty, $module_name, $pRules)
        . '<h4 class="fw-grid-title">'._tr('GeoIP').'</h4>'
        . '<div class="fw-grid" id="geoip_grid">'.renderRulesGrid($smarty, $module_name, $pDB, true).'</div>'
        . '<h4 class="fw-grid-title">'._tr('Rules').'</h4>'
        . '<div class="fw-grid" id="rules_grid">'.renderRulesGrid($smarty, $module_name, $pDB, false).'</div>';
}

/**
 * Barra única de acciones. Sustituye a las barras que paloSantoGrid genera
 * por cada grilla para que las dos tablas compartan un solo juego de botones.
 */
function renderToolbar($smarty, $module_name, $pRules)
{
    $isFirst     = $pRules->isFirstTime();
    $isExecuted  = $pRules->isExecutedInSystem();
    $hasSnapshot = $pRules->hasSnapshot();
    $applyLabel  = $isFirst ? _tr('Activate firewall') : _tr('Apply changes');

    $html  = '<div class="fw-toolbar">';
    $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="fw_delete" data-empty="'
           . htmlspecialchars(_tr('No rules were selected'), ENT_COMPAT, 'UTF-8') . '" data-confirm="'
           . htmlspecialchars(_tr('Are you sure you wish to delete the selected rules?'), ENT_COMPAT, 'UTF-8') . '">'
           . '<i class="ti ti-trash"></i> '._tr('Delete').'</button>';
    $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="fw_deactivate" data-confirm="'
           . htmlspecialchars(_tr('Are you sure you wish to deactivate the firewall?'), ENT_COMPAT, 'UTF-8') . '">'
           . '<i class="ti ti-power"></i> '._tr('Deactivate firewall').'</button>';
    $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="fw_apply">'
           . '<i class="ti ti-check"></i> '.$applyLabel.'</button>';
    if (!$isFirst && !$isExecuted && $hasSnapshot) {
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="fw_discard" data-confirm="'
               . htmlspecialchars(_tr('Are you sure you wish to discard the changes?'), ENT_COMPAT, 'UTF-8') . '">'
               . '<i class="ti ti-arrow-back-up"></i> '._tr('Discard changes').'</button>';
    }
    $html .= '<a class="btn btn-sm btn-outline-primary" href="?menu='.$module_name.'&amp;action=new">'
           . '<i class="ti ti-plus"></i> '._tr('New Rule').'</a>';
    $html .= '</div>';

    $html .= '<div id="fw_alert" class="iss-alert" style="display:none;" role="status" data-error="'
           . htmlspecialchars(_tr('The change could not be saved'), ENT_COMPAT, 'UTF-8') . '"></div>';
    $html .= '<form id="fw_action_form" method="POST" action="?menu='.$module_name.'" style="display:none;">'
           . issabel_csrf_field()
           . '<div id="fw_action_fields"></div></form>';

    return $html;
}

function renderRulesGrid($smarty, $module_name, &$pDB, $isGeo)
{
    $pRules = new paloSantoRules($pDB);
    $oGrid  = new paloSantoGrid($smarty);

    // Cabeceras vacías para Orden/Activado/Opciones, como en la interfaz
    // anterior; el texto queda sólo para lectores de pantalla.
    $hidden = function ($text) {
        return '<span class="fw-sr-only">'.$text.'</span>';
    };

    $oGrid->setColumns(array(
        '',
        $hidden(_tr('Order')),
        _tr('Traffic'),
        _tr('Target'),
        _tr('Interface'),
        _tr('IP Source'),
        _tr('IP Destiny'),
        _tr('Protocol'),
        _tr('Details'),
        $hidden(_tr('Activated')),
        $hidden(_tr('Options')),
    ));

    $oGrid->pagingShow(false);
    $oGrid->setURL(array('menu' => $module_name));

    // Mapa id de puerto => nombre, para mostrar los detalles de las reglas.
    $portNames = array();
    if (!$isGeo) {
        $oPort = new paloSantoPortService($pDB);
        foreach (array($oPort->getTCPortNumbers(), $oPort->getUDPortNumbers()) as $list) {
            if (is_array($list)) foreach ($list as $p) $portNames[$p['id']] = $p['name'];
        }
    }

    $trafficFiles = array(
        'INPUT'   => 'fw_input.gif',
        'OUTPUT'  => 'fw_output.gif',
        'FORWARD' => 'fw_forward.gif',
    );

    $result = $isGeo ? $pRules->ObtainRulesGEOIP(100000, 0) : $pRules->ObtainRules(100000, 0);
    $data = array();
    if (is_array($result)) {
        foreach ($result as $row) {
            $id = (int)$row['id'];
            $activated = ($row['activated'] == '1');
            $order = $isGeo ? ((int)$row['rule_order'] - 100000) : (int)$row['rule_order'];

            $arrTmp[0] = "<input type='checkbox' name='del_x[]' value='".$id."'>";
            $arrTmp[1] = $order;

            $traffic = (string)$row['traffic'];
            if (isset($trafficFiles[$traffic])) {
                $label = htmlspecialchars(_tr($traffic), ENT_COMPAT, 'UTF-8');
                $arrTmp[2] = '<img class="fw-icon" src="modules/'.$module_name.'/images/'.$trafficFiles[$traffic]
                           . '" alt="'.$label.'" title="'.$label.'">';
            } else {
                $arrTmp[2] = htmlspecialchars($traffic, ENT_COMPAT, 'UTF-8');
            }

            $target = (string)$row['target'];
            $targetFile = ($target === 'ACCEPT') ? 'target_accept.gif' : 'target_drop.gif';
            $targetLabel = htmlspecialchars(_tr($target), ENT_COMPAT, 'UTF-8');
            $arrTmp[3] = '<img class="fw-icon" src="modules/'.$module_name.'/images/'.$targetFile
                       . '" alt="'.$targetLabel.'" title="'.$targetLabel.'">';

            $iface = '';
            if ((string)$row['eth_in'] !== '') $iface .= _tr('INPUT').': '.htmlspecialchars((string)$row['eth_in'], ENT_COMPAT, 'UTF-8');
            if ((string)$row['eth_out'] !== '') {
                if ($iface !== '') $iface .= '<br>';
                $iface .= _tr('OUTPUT').': '.htmlspecialchars((string)$row['eth_out'], ENT_COMPAT, 'UTF-8');
            }
            $arrTmp[4] = $iface;

            $arrTmp[5] = htmlspecialchars((string)$row['ip_source'], ENT_COMPAT, 'UTF-8');
            $arrTmp[6] = htmlspecialchars((string)$row['ip_destiny'], ENT_COMPAT, 'UTF-8');

            if ((string)$row['protocol'] === 'GEOIP') {
                $arrTmp[7] = '<span class="fw-protocol" title="GEOIP"><i class="ti ti-world"></i>'
                           . '<span class="fw-sr-only">GEOIP</span></span>';
            } else {
                $arrTmp[7] = htmlspecialchars((string)$row['protocol'], ENT_COMPAT, 'UTF-8');
            }

            $arrTmp[8] = ruleDetails($row, $portNames);

            $checked = $activated ? ' checked="checked"' : '';
            $arrTmp[9] = '<div class="form-check form-switch fw-switch">'
                       . '<input type="checkbox" class="form-check-input fw-toggle" role="switch" data-id="'.$id.'"'
                       . $checked.' aria-label="'.htmlspecialchars(_tr("Activated"), ENT_COMPAT, 'UTF-8').'"></div>';

            $arrTmp[10] = '<span class="fw-drag" draggable="true" tabindex="0" role="button" data-id="'.$id.'"'
                        . ' data-grid="'.($isGeo ? 'geoip' : 'rules').'"'
                        . ' title="'.htmlspecialchars(_tr("Drag to reorder"), ENT_COMPAT, 'UTF-8').'"'
                        . ' aria-label="'.htmlspecialchars(_tr("Drag to reorder"), ENT_COMPAT, 'UTF-8').'">'
                        . '<i class="ti ti-grip-vertical"></i></span>'
                        . '<a class="fw-edit" href="?menu='.$module_name.'&amp;action=edit&amp;id='.$id.'"'
                        . ' title="'.htmlspecialchars(_tr("Edit"), ENT_COMPAT, 'UTF-8').'"'
                        . ' aria-label="'.htmlspecialchars(_tr("Edit"), ENT_COMPAT, 'UTF-8').'">'
                        . '<i class="ti ti-edit"></i></a>';

            $data[] = $arrTmp;
        }
    }
    $oGrid->setData($data);

    return $oGrid->fetchGrid();
}

function ruleDetails($row, $portNames)
{
    $ret = '';
    if ($row['protocol'] == 'ICMP') {
        $ret = _tr('Type').': '.htmlspecialchars((string)($row['icmp_type'] ?? ''), ENT_COMPAT, 'UTF-8');
    } elseif ($row['protocol'] == 'GEOIP') {
        $continents = (string)($row['continents'] ?? '');
        $countries  = (string)($row['countries'] ?? '');
        if ($continents !== '') $ret .= _tr('Continents').': '.htmlspecialchars($continents, ENT_COMPAT, 'UTF-8');
        if ($countries !== '') {
            if ($ret !== '') $ret .= '<br>';
            $ret .= _tr('Countries').': '.htmlspecialchars($countries, ENT_COMPAT, 'UTF-8');
        }
    } elseif ($row['protocol'] == 'STATE') {
        $ret = htmlspecialchars((string)($row['state'] ?? ''), ENT_COMPAT, 'UTF-8');
    } elseif ($row['protocol'] != 'ALL') {
        $sport = isset($portNames[$row['sport']]) ? $portNames[$row['sport']] : (string)$row['sport'];
        $dport = isset($portNames[$row['dport']]) ? $portNames[$row['dport']] : (string)$row['dport'];
        if ($sport !== '' || $dport !== '') {
            $ret = _tr('Port Source').': '.htmlspecialchars($sport, ENT_COMPAT, 'UTF-8')
                 . '<br>'._tr('Port Destine').': '.htmlspecialchars($dport, ENT_COMPAT, 'UTF-8');
        }
    }
    return $ret;
}

function editRulePage($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $id = (int)getParameter('id');
    $pRules = new paloSantoRules($pDB);
    $row = $pRules->getRule($id);
    if (!is_array($row)) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), _tr("The rule could not be loaded")));
        return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }

    $src = explode('/', $row['ip_source']);
    $dst = explode('/', $row['ip_destiny']);
    $values = array(
        'id_traffic'      => $row['traffic'],
        'interface_in'    => $row['eth_in'],
        'interface_out'   => $row['eth_out'],
        'ip_source'       => isset($src[0]) ? $src[0] : '0.0.0.0',
        'mask_source'     => isset($src[1]) ? $src[1] : '24',
        'ip_destin'       => isset($dst[0]) ? $dst[0] : '0.0.0.0',
        'mask_destin'     => isset($dst[1]) ? $dst[1] : '24',
        'port_in'         => $row['sport'],
        'port_out'        => $row['dport'],
        'type_icmp'       => $row['icmp_type'],
        'id_ip'           => $row['number_ip'],
        'id_protocol'     => $row['protocol'],
        'target'          => $row['target'],
        'orden'           => $row['rule_order'],
        'state'           => $row['state'],
        'geoipcountries'  => $row['countries'] !== '' ? explode(',', $row['countries']) : array(),
        'geoipcontinents' => $row['continents'] !== '' ? explode(',', $row['continents']) : array(),
        'established'     => (strpos((string)$row['state'], 'Established') !== false) ? 'on' : '',
        'related'         => (strpos((string)$row['state'], 'Related') !== false) ? 'on' : '',
        'id'              => $id,
    );
    return constructForm($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $values, 'edit');
}

function constructForm($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $arrValues, $action="")
{
    // Valores por defecto del formulario nuevo.
    $arrValues += array(
        'id_traffic'      => 'INPUT',
        'id_protocol'     => 'ALL',
        'ip_source'       => '0.0.0.0',
        'mask_source'     => '24',
        'ip_destin'       => '0.0.0.0',
        'mask_destin'     => '24',
        'target'          => 'ACCEPT',
        'id'              => '',
        'state'           => '',
        'orden'           => '',
        'geoipcountries'  => array(),
        'geoipcontinents' => array(),
        'established'     => '',
        'related'         => '',
    );

    $arrFormRules = createFieldForm($pDB,$arrValues);

    // Recuadro rojo por campo, conservando form-control.
    $errors = isset($arrValues['errors']) ? $arrValues['errors'] : array();
    foreach ($errors as $f => $msg) {
        if ($msg === '' || !isset($arrFormRules[$f])) continue;
        if ($arrFormRules[$f]['INPUT_TYPE'] === 'SELECT') {
            // En SELECT, INPUT_EXTRA_PARAM es el mapa de opciones: la clase
            // va en CLASS o se añadiría una opción falsa.
            $arrFormRules[$f]['CLASS'] = 'is-invalid';
            continue;
        }
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
    $smarty->assign("icon", "modules/$module_name/images/security_firewall_rules.png");
    $smarty->assign("ERRORS", $errors);

    $traffic = isset($arrValues['id_traffic']) ? $arrValues['id_traffic'] : "";
    $trafficClass = isset($errors['id_traffic']) ? 'form-select is-invalid' : 'form-select';
    $traffic_html = '<select class="'.$trafficClass.'" id="id_traffic" name="id_traffic" onChange="showElementByTraffic();">'
        . '<option value="INPUT"'.($traffic == "INPUT" ? ' selected' : '').'>'._tr("INPUT").'</option>'
        . '<option value="OUTPUT"'.($traffic == "OUTPUT" ? ' selected' : '').'>'._tr("OUTPUT").'</option>'
        . '<option value="FORWARD"'.($traffic == "FORWARD" ? ' selected' : '').'>'._tr("FORWARD").'</option>'
        . '</select>';

    $protocol = isset($arrValues['id_protocol']) ? $arrValues['id_protocol'] : "";
    $protocolClass = isset($errors['id_protocol']) ? 'form-select is-invalid' : 'form-select';
    $protocol_html = '<select class="'.$protocolClass.'" id="id_protocol" name="id_protocol" onChange="showElementByProtocol();">';
    foreach (array('ALL','TCP','UDP','ICMP','IP','STATE','GEOIP') as $p) {
        $protocol_html .= '<option value="'.$p.'"'.($protocol == $p ? ' selected' : '').'>'._tr($p).'</option>';
    }
    $protocol_html .= '</select>';

    $smarty->assign("traffic_html", $traffic_html);
    $smarty->assign("protocol_html", $protocol_html);
    $smarty->assign("traffic_label", _tr("Traffic"));
    $smarty->assign("protocol_label", _tr("Protocol"));
    $smarty->assign("ip_detail", _tr("IP DETAILS"));
    $smarty->assign("protocol_detail", _tr("PROTOCOL DETAILS"));
    $smarty->assign("action_detail", _tr("ACTION DETAIL"));
    $smarty->assign("MODE", $action === 'edit' ? 'edit' : 'new');
    $smarty->assign("VALUES", $arrValues);

    $title = ($action == "edit") ? _tr("Edit Rule") : _tr("New Rule");
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new.tpl", $title, $arrValues);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $smarty->getTemplateVars('SECURITY_ALERT').$contenidoModulo;
}

function createFieldForm($pDB,$arrValues = array())
{
    global $arrConf;

    $pDBcountries = new paloDB($arrConf['dsn_conn_database_countries']);
    $query = "SELECT continents.name AS continent,countries.code AS code,countries.name AS country ";
    $query.= "FROM countries LEFT JOIN continents ON continent_code=continents.code ";
    $query.= "ORDER BY continent_code,countries.name";
    $arrCountries = $pDBcountries->fetchTable($query, true);
    $arrPais = array();
    foreach($arrCountries as $idx=>$data) {
        $arrPais[$data['continent']][$data['code']]=$data['country'];
    }

    $query = "SELECT code,name FROM continents";
    $arrContinents = $pDBcountries->fetchTable($query, true);
    $arrContinente = array();
    foreach($arrContinents as $idx=>$data) {
        $arrContinente[$data['code']]=$data['name'];
    }

    $oPort = new paloSantoPortService($pDB);
    $pRules = new paloSantoRules($pDB);
    if(isset($arrValues['id_protocol']))
        $Ports = ($arrValues['id_protocol'] == "TCP") ? $oPort->getTCPortNumbers() : $oPort->getUDPortNumbers();
    else
        $Ports = $oPort->getTCPortNumbers();
    $type = $oPort->getICMPType();
    $protocol_number = $oPort ->getIPProtNumber();
    $arrInterface['ANY'] = _tr('ANY');
    $arrInterfacetmp = $pRules->obtener_nombres_interfases_red();

    $vlan_dictionary = array();
    $vlan_dictionary['eth0.100']='WAN';
    $vlan_dictionary['eth0.200']='LAN';

    $hideNic=array();
    foreach($arrInterfacetmp as $dev=>$name) {
        if(preg_match("/\./",$dev)) {
            // if vlan, hide parent/real nic
            $parts = preg_split("/\./",$dev);
            $hideNic[] = $parts[0];
        } else if(preg_match("/^dummy/",$dev)) {
            $hideNic[] = $dev;
        }
        if($dev==$name) {
            if(isset($vlan_dictionary[$dev])) $arrInterfacetmp[$dev]=$vlan_dictionary[$dev];
        }
    }
    foreach($hideNic as $nic) {
        unset($arrInterfacetmp[$nic]);
    }

    foreach($arrInterfacetmp as $key => $value)
        $arrInterface[$key] = $value;
    $arrTarget    = array("ACCEPT" => _tr("ACCEPT"), "DROP" => _tr("DROP"), "REJECT" => _tr("REJECT"));
    $arrType['ANY'] = _tr('ANY');
    foreach($type as $key => $value){
        $arrType[$value["id"]] = $value["name"];
    }
    $arrPort['ANY'] = _tr('ANY');
    foreach($Ports as $key => $value){
        $arrPort[$value['id']] = $value['name'];
    }
    $arrIP['ANY'] = _tr('ANY');
    foreach($protocol_number as $key => $value){
        $arrIP[$value['id']] = $value['name'];
    }

    $arrFields = array(
        "interface_in"    => array( "LABEL"                  => _tr("Interface IN"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrInterface,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "interface_out"   => array( "LABEL"                  => _tr("Interface OUT"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrInterface,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "ip_source"       => array( "LABEL"                  => _tr("IP Source"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "placeholder" => "0.0.0.0"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
            ),
        "mask_source"     => array( "LABEL"                  => _tr("Mask"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "placeholder" => "24"),
            "VALIDATION_TYPE"        => "numeric",
            "VALIDATION_EXTRA_PARAM" => ""
            ),
        "ip_destin"      => array(  "LABEL"                  => _tr("IP Destiny"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "placeholder" => "0.0.0.0"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
            ),
        "mask_destin"     => array( "LABEL"                  => _tr("Mask"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "placeholder" => "24"),
            "VALIDATION_TYPE"        => "numeric",
            "VALIDATION_EXTRA_PARAM" => ""
            ),
        "port_in"         => array( "LABEL"                  => _tr("Port Source"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrPort,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "port_out"        => array( "LABEL"                  => _tr("Port Destine"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrPort,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "type_icmp"       => array( "LABEL"                  => _tr("Type"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrType,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "id_ip"           => array( "LABEL"                  => _tr("ID"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrIP,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "established"     => array( "LABEL"                  => _tr("Established"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "CHECKBOX",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "related"         => array( "LABEL"                  => _tr("Related"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "CHECKBOX",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "geoipcountries"  => array( "LABEL"                  => _tr("Countries"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrPais,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            "MULTIPLE"               => TRUE
            ),
        "geoipcontinents"  => array( "LABEL"                  => _tr("Continents"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrContinente,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            "MULTIPLE"               => TRUE
            ),
        "target"          => array( "LABEL"                  => _tr("Target"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrTarget,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "orden"           => array(  "LABEL"                  => _tr("Order"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "id"              => array(  "LABEL"                  => "",
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "HIDDEN",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            ),
        "state"           => array(  "LABEL"                  => "",
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "HIDDEN",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "yes",
            )
    );
    return $arrFields;
}

/**
 * Valida los valores del formulario. Devuelve un array campo => mensaje.
 */
function validateRuleInput(&$values)
{
    $errors = array();
    $traffic = isset($values['traffic']) ? $values['traffic'] : '';
    if (!in_array($traffic, array('INPUT','OUTPUT','FORWARD'), true))
        $errors['id_traffic'] = sprintf(_tr('%s is invalid'), _tr('Traffic'));

    if ($traffic == 'INPUT' || $traffic == 'FORWARD') {
        if (empty($values['interface_in'])) $errors['interface_in'] = sprintf(_tr('%s is required'), _tr('Interface IN'));
    }
    if ($traffic == 'OUTPUT' || $traffic == 'FORWARD') {
        if (empty($values['interface_out'])) $errors['interface_out'] = sprintf(_tr('%s is required'), _tr('Interface OUT'));
    }

    foreach (array('ip_source' => 'IP Source', 'ip_destin' => 'IP Destiny') as $f => $label) {
        $v = isset($values[$f]) ? trim((string)$values[$f]) : '';
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_IP))
            $errors[$f] = sprintf(_tr('%s is invalid'), _tr($label));
    }
    foreach (array('mask_source' => 'Mask', 'mask_destin' => 'Mask') as $f => $label) {
        $v = isset($values[$f]) ? trim((string)$values[$f]) : '';
        if ($v !== '' && (!preg_match('/^[0-9]{1,2}$/', $v) || (int)$v > 32))
            $errors[$f] = sprintf(_tr('%s is invalid'), _tr($label));
    }
    // Una IP concreta con máscara 0 no es una regla válida.
    if (!empty($values['ip_source']) && $values['ip_source'] !== '0.0.0.0'
        && isset($values['mask_source']) && (string)$values['mask_source'] === '0')
        $errors['mask_source'] = _tr('Wrong Mask');
    if (!empty($values['ip_destin']) && $values['ip_destin'] !== '0.0.0.0'
        && isset($values['mask_destin']) && (string)$values['mask_destin'] === '0')
        $errors['mask_destin'] = _tr('Wrong Mask');

    $protocol = isset($values['protocol']) ? $values['protocol'] : '';
    if (!in_array($protocol, array('ALL','TCP','UDP','ICMP','IP','STATE','GEOIP'), true))
        $errors['id_protocol'] = sprintf(_tr('%s is invalid'), _tr('Protocol'));
    if ($protocol == 'TCP' || $protocol == 'UDP') {
        if (empty($values['port_in']))  $errors['port_in'] = sprintf(_tr('%s is required'), _tr('Port Source'));
        if (empty($values['port_out'])) $errors['port_out'] = sprintf(_tr('%s is required'), _tr('Port Destine'));
    } elseif ($protocol == 'ICMP') {
        if (empty($values['type_icmp'])) $errors['type_icmp'] = sprintf(_tr('%s is required'), _tr('Type'));
    } elseif ($protocol == 'IP') {
        if (empty($values['id_ip'])) $errors['id_ip'] = sprintf(_tr('%s is required'), _tr('ID'));
    } elseif ($protocol == 'STATE') {
        if (empty($values['state'])) $errors['state'] = _tr("You have to select at least one state");
    } elseif ($protocol == 'GEOIP') {
        if (empty($values['geoipcountries']) && empty($values['geoipcontinents']))
            $errors['geoipcountries'] = _tr("You have to list some countries or continents");
    }

    if (empty($values['target'])) $errors['target'] = sprintf(_tr('%s is required'), _tr('Target'));

    return $errors;
}

function saveRulePage($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $id = trim((string)getParameter('id'));
    $traffic = getParameter('id_traffic');
    $protocol = getParameter('id_protocol');
    $ip_source = trim((string)getParameter('ip_source'));
    $ip_destin = trim((string)getParameter('ip_destin'));
    $mask_source = trim((string)getParameter('mask_source'));
    $mask_destin = trim((string)getParameter('mask_destin'));
    $geoipCountriesRaw = getParameter('geoipcountries');
    $geoipContinentsRaw = getParameter('geoipcontinents');

    $values = array(
        'id'              => $id,
        'id_traffic'      => $traffic,
        'traffic'         => $traffic,
        'interface_in'    => getParameter('interface_in'),
        'interface_out'   => getParameter('interface_out'),
        'ip_source'       => $ip_source === '' ? '0.0.0.0' : $ip_source,
        'mask_source'     => $mask_source,
        'ip_destin'       => $ip_destin === '' ? '0.0.0.0' : $ip_destin,
        'mask_destin'     => $mask_destin,
        'port_in'         => getParameter('port_in'),
        'port_out'        => getParameter('port_out'),
        'type_icmp'       => getParameter('type_icmp'),
        'id_ip'           => getParameter('id_ip'),
        'id_protocol'     => $protocol,
        'protocol'        => $protocol,
        'target'          => getParameter('target'),
        'orden'           => getParameter('orden'),
        'geoipcountries'  => $geoipCountriesRaw,
        'geoipcontinents' => $geoipContinentsRaw,
    );

    if ($protocol == 'STATE') {
        $established = getParameter('established');
        $related = getParameter('related');
        $state = array();
        if ($established == 'on') $state[] = 'Established';
        if ($related == 'on') $state[] = 'Related';
        $values['state'] = implode(',', $state);
        $values['established'] = ($established == 'on') ? 'on' : '';
        $values['related'] = ($related == 'on') ? 'on' : '';
    } elseif ($protocol == 'GEOIP') {
        // saveRule()/updateRule() reciben los arrays y hacen el implode.
        $values['geoipcountries'] = is_array($geoipCountriesRaw) ? $geoipCountriesRaw : array();
        $values['geoipcontinents'] = is_array($geoipContinentsRaw) ? $geoipContinentsRaw : array();
        $values['state'] = '';
    } else {
        $values['state'] = '';
        $values['geoipcountries'] = array();
        $values['geoipcontinents'] = array();
    }

    $errors = validateRuleInput($values);
    if (count($errors) > 0) {
        // Conserva las selecciones múltiples tal como llegaron.
        if (is_array($geoipCountriesRaw))  $values['geoipcountries']  = $geoipCountriesRaw;
        if (is_array($geoipContinentsRaw)) $values['geoipcontinents'] = $geoipContinentsRaw;
        $values['errors'] = $errors;
        return constructForm($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $values, $id === '' ? 'new' : 'edit');
    }

    // Normaliza red/máscara igual que la versión anterior.
    $pNet = new paloNetwork();
    $pRules = new paloSantoRules($pDB);
    $ip_source = ($values['ip_source'] == '') ? '0.0.0.0' : $values['ip_source'];
    $ip_destin = ($values['ip_destin'] == '') ? '0.0.0.0' : $values['ip_destin'];
    if ($ip_source != '0.0.0.0' && $values['mask_source'] !== '' && $values['mask_source'] !== '0') {
        $ip_source = $pNet->getNetAdress($ip_source, $values['mask_source']);
    }
    if ($ip_destin != '0.0.0.0' && $values['mask_destin'] !== '' && $values['mask_destin'] !== '0') {
        $ip_destin = $pNet->getNetAdress($ip_destin, $values['mask_destin']);
    }
    // saveRule()/updateRule() añaden la máscara: se pasan por separado.
    $values['ip_source'] = $ip_source;
    $values['ip_destin'] = $ip_destin;
    if ($ip_source == '0.0.0.0') $values['mask_source'] = '0';
    if ($ip_destin == '0.0.0.0') $values['mask_destin'] = '0';

    // Sólo se guardan los campos que aplican al tráfico y al protocolo.
    if ($values['traffic'] != 'OUTPUT' && $values['traffic'] != 'FORWARD') $values['interface_out'] = null;
    if ($values['traffic'] != 'INPUT'  && $values['traffic'] != 'FORWARD') $values['interface_in'] = null;
    if ($values['protocol'] != 'TCP' && $values['protocol'] != 'UDP') {
        $values['port_in'] = null;
        $values['port_out'] = null;
    }
    if ($values['protocol'] != 'ICMP') $values['type_icmp'] = null;
    if ($values['protocol'] != 'IP')   $values['id_ip'] = null;

    $ok = ($id === '') ? $pRules->saveRule($values) : $pRules->updateRule($values, $id);
    if ($ok) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"),
            $id === '' ? _tr("Successful Save") : _tr("Successful Update")));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pRules->errMsg));
    }

    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function toggleRule($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $action)
{
    $id = (int)getParameter($action);
    $pRules = new paloSantoRules($pDB);
    $ok = ($action == 'activate') ? $pRules->setActivated($id) : $pRules->setDesactivated($id);

    if (getParameter('ajax')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? _tr('Update correctly')
                                  : ($pRules->errMsg !== '' ? $pRules->errMsg : _tr('The change could not be saved')),
        ));
        return NULL;
    }

    $smarty->assign("SECURITY_ALERT", $ok
        ? issabel_alert_html('success', _tr("Message"), _tr("Update correctly"))
        : issabel_alert_html('error', _tr("ERROR"), $pRules->errMsg));
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

/**
 * Guarda el nuevo orden que llega del drag-and-drop o del teclado.
 * Los ids deben ser de la misma familia (reglas o GeoIP) que la grilla.
 */
function reorderRules(&$pDB)
{
    header('Content-Type: application/json; charset=utf-8');
    $ids = json_decode((string)getParameter('ids'), true);
    $isGeo = ((int)getParameter('geoip') === 1);

    if (!is_array($ids) || count($ids) === 0) {
        echo json_encode(array('status' => 'error', 'message' => _tr('The order could not be saved')));
        return NULL;
    }

    $pRules = new paloSantoRules($pDB);
    $seen = array();
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id <= 0 || isset($seen[$id])) {
            echo json_encode(array('status' => 'error', 'message' => _tr('The order could not be saved')));
            return NULL;
        }
        $rule = $pRules->getRule($id);
        if (!is_array($rule) || (($rule['protocol'] === 'GEOIP') !== $isGeo)) {
            echo json_encode(array('status' => 'error', 'message' => _tr('The order could not be saved')));
            return NULL;
        }
        $seen[$id] = true;
    }

    $order = ($isGeo ? 100000 : 0) + 1;
    foreach ($ids as $id) {
        if (!$pRules->updateOrder((int)$id, $order++)) {
            echo json_encode(array('status' => 'error', 'message' => $pRules->errMsg));
            return NULL;
        }
    }

    echo json_encode(array('status' => 'success', 'message' => _tr('Update correctly')));
    return NULL;
}

function discardChanges($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRules = new paloSantoRules($pDB);
    if (!$pRules->hasSnapshot()) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('warning', _tr('Message'),
            _tr('There is no applied state to restore')));
    } elseif ($pRules->discardChanges()) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr('Message'),
            _tr('Changes discarded')));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr('ERROR'),
            _tr('The changes could not be discarded')));
    }
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function moveRule($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $action)
{
    $id = (int)getParameter($action);
    $pRules = new paloSantoRules($pDB);
    $rule = $pRules->getRule($id);
    if (is_array($rule)) {
        $isGeo = ($rule['protocol'] == 'GEOIP');
        $list = $isGeo ? $pRules->ObtainRulesGEOIP(100000, 0) : $pRules->ObtainRules(100000, 0);
        $index = -1;
        foreach ($list as $i => $row) if ((int)$row['id'] === $id) { $index = $i; break; }
        $swap = ($action == 'move_up') ? $index - 1 : $index + 1;
        if ($index >= 0 && isset($list[$swap])) {
            $a = $list[$index]['rule_order'];
            $b = $list[$swap]['rule_order'];
            $pRules->updateOrder($list[$index]['id'], $b);
            $pRules->updateOrder($list[$swap]['id'], $a);
        }
    }
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function activateFirewall($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRules = new paloSantoRules($pDB);
    $bFirstTime = $pRules->isFirstTime();
    $pRules->noMoreFirstTime();
    if (!$pRules->activateRules()) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), $pRules->errMsg));
        return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"),
        $bFirstTime ? _tr("The firewall has been activated") : _tr("The rules have been executed in the system")));
    $pRules->updateExecutedInSystem();
    // Snapshot del estado que acaba de quedar aplicado; es la base de
    // "Descartar cambios".
    if (!$pRules->snapshotApplied()) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('warning', _tr('Message'),
            _tr('The applied state could not be saved')));
    }
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function desactivateFirewall($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRules = new paloSantoRules($pDB);
    if ($pRules->flushRules() && $pRules->setFirstTime()) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"), _tr("The firewall has been desactivated")));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), _tr("The firewall could not be desactivated")));
    }
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function deleteRules($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRules = new paloSantoRules($pDB);
    $ids = isset($_POST['del_x']) && is_array($_POST['del_x']) ? $_POST['del_x'] : array();
    if (count($ids) === 0) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('warning', _tr("Message"), _tr("No rules were selected")));
        return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    $count = 0;
    $failed = array();
    foreach ($ids as $id) {
        if ($pRules->deleteRule((int)$id)) $count++;
        else $failed[] = htmlspecialchars((string)$pRules->errMsg, ENT_COMPAT, 'UTF-8');
    }
    if (count($failed) === 0) {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('success', _tr("Message"),
            sprintf(_tr("%d rules were deleted"), $count)));
    } else {
        $smarty->assign("SECURITY_ALERT", issabel_alert_html('error', _tr("ERROR"), implode(' ', $failed)));
    }
    return showRulesPage($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function getAction()
{
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    if($isPost && getParameter("save"))            return "save";
    else if(getParameter("new"))        return "new";
    else if($isPost && getParameter("delete"))     return "delete";
    else if($isPost && getParameter("activatefirewall")) return "activatefirewall";
    else if($isPost && getParameter("desactivatefirewall")) return "desactivatefirewall";
    else if($isPost && getParameter("activate"))   return "activate";
    else if($isPost && getParameter("deactivate")) return "deactivate";
    else if($isPost && getParameter("reorder"))    return "reorder";
    else if($isPost && getParameter("discardchanges")) return "discardchanges";
    else if($isPost && getParameter("move_up"))    return "move_up";
    else if($isPost && getParameter("move_down"))  return "move_down";
    else if(getParameter("cancel"))     return "cancel";
    else if(getParameter("action") == "edit") return "edit";
    else if(getParameter("action") == "new")  return "new";
    else return "report";
}
?>
