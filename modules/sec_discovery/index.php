<?php
/*
 * sec_discovery — sugiere puertos y peers/trunks a partir de la configuración
 * generada por IssabelPBX. No aplica nada solo: cada acción es un POST con
 * CSRF y queda auditada.
 */

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoNetwork.class.php";
include_once "modules/sec_rules/libs/issabelFwAudit.class.php";
include_once "modules/sec_rules/libs/paloSantoRules.class.php";
include_once "modules/sec_whitelist/libs/Issabelwhitelist.class.php";

function _moduleContent(&$smarty, $module_name)
{
    include_once "modules/$module_name/configs/default.conf.php";
    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    $templates_dir = isset($arrConf['templates_dir']) ? $arrConf['templates_dir'] : 'themes';
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $local_templates_dir = "$base_dir/modules/$module_name/$templates_dir/".$arrConf['theme'];

    $pDB = new paloDB($arrConf['dsn_conn_database']);
    $smarty->assign('SECURITY_ALERT', '');

    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'addport') {
        addPort($smarty, $pDB);
    } elseif ($action === 'addwhitelist') {
        addWhitelist($smarty, $pDB);
    } elseif ($action === 'addrule') {
        addRule($smarty, $pDB);
    }

    return renderDiscovery($smarty, $module_name, $local_templates_dir, $pDB);
}

function discover()
{
    $out = $ret = NULL;
    exec('/usr/bin/issabel-helper fwdiscover 2>&1', $out, $ret);
    $json = json_decode(implode("\n", $out), true);
    if ($ret != 0 || !is_array($json)) {
        return array('services' => array(), 'peers' => array(), 'rtp' => array(), 'catalog' => array(),
            'warnings' => array(_tr('The discovery helper could not read the Asterisk configuration')));
    }
    return $json;
}

function renderDiscovery($smarty, $module_name, $local_templates_dir, &$pDB)
{
    $data = discover();
    $catalog = array();
    foreach ($data['catalog'] as $p) {
        $catalog[strtoupper($p['protocol']).'|'.$p['details']] = $p;
    }
    foreach ($data['services'] as &$s) {
        $s['in_catalog'] = isset($catalog[strtoupper($s['proto']).'|'.$s['port']]);
        $s['catalog_protocol'] = ($s['transport'] === 'udp') ? 'UDP' : 'TCP';
        $s['catalog_name'] = substr(strtoupper($s['proto']).'-'.$s['port'], 0, 15);
    }
    unset($s);

    $smarty->assign('SERVICES', $data['services']);
    $smarty->assign('PEERS', $data['peers']);
    $smarty->assign('RTP', $data['rtp']);
    $smarty->assign('WARNINGS', $data['warnings']);
    $smarty->assign('MODULE', $module_name);
    $smarty->assign('CSRF_FIELD', issabel_csrf_field());
    $smarty->assign('TITLE', _tr('Discovery'));
    $smarty->assign('ADD', _tr('Add'));
    $smarty->assign('ADD_PORT', _tr('Add to catalog'));
    $smarty->assign('ADD_WL', _tr('Add to whitelist'));
    $smarty->assign('ADD_RULE', _tr('Create rule (disabled)'));
    $smarty->assign('PROTO', _tr('Protocol'));
    $smarty->assign('TRANSPORT', _tr('Transport'));
    $smarty->assign('PORT', _tr('Port'));
    $smarty->assign('SOURCE', _tr('Source'));
    $smarty->assign('CONFIDENCE', _tr('Confidence'));
    $smarty->assign('NAME', _tr('Name'));
    $smarty->assign('TECH', _tr('Technology'));
    $smarty->assign('HOST', _tr('Host'));
    $smarty->assign('DYNAMIC', _tr('Dynamic'));
    $smarty->assign('RTP_RANGE', _tr('RTP range'));
    $smarty->assign('NO_SERVICES', _tr('No services were detected'));
    $smarty->assign('NO_PEERS', _tr('No static peers were detected'));
    $smarty->assign('WARNING', _tr('Warning'));

    return $smarty->fetch("$local_templates_dir/discovery.tpl");
}

function addPort($smarty, &$pDB)
{
    $name = trim((string)getParameter('name'));
    $protocol = strtoupper(trim((string)getParameter('protocol')));
    $details = trim((string)getParameter('details'));
    $comment = trim((string)getParameter('comment'));

    if ($name === '' || !in_array($protocol, array('TCP','UDP'), true)
        || !preg_match('/^[0-9]{1,5}(:[0-9]{1,5})?$/', $details)) {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), _tr('Invalid port definition')));
        return;
    }
    $ok = $pDB->genQuery("INSERT INTO port(name,protocol,details,comment) VALUES(?,?,?,?)",
        array($name, $protocol, $details, $comment));
    issabelFwAudit::log('discovery.port.add', $ok ? 'ok' : 'error',
        array('name' => $name, 'protocol' => $protocol, 'details' => $details));
    $smarty->assign('SECURITY_ALERT', $ok
        ? issabel_alert_html('success', _tr('Message'), _tr('Port added to the catalog'))
        : issabel_alert_html('error', _tr('ERROR'), $pDB->errMsg));
}

function addWhitelist($smarty, &$pDB)
{
    $ip = trim((string)getParameter('ip'));
    $oWl = new Issabelwhitelist($pDB);
    $oWl->checkTable();
    if ($oWl->validateIpOrMask($ip) !== '') {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), _tr('Invalid IP address or network')));
        return;
    }
    $ok = $oWl->savewhitelist($ip, 'discovery');
    issabelFwAudit::log('discovery.peer.whitelist', $ok ? 'ok' : 'error', array('ip' => $ip));
    $smarty->assign('SECURITY_ALERT', $ok
        ? issabel_alert_html('success', _tr('Message'), _tr('IP address added to the whitelist'))
        : issabel_alert_html('error', _tr('ERROR'), $oWl->errMsg));
}

function addRule($smarty, &$pDB)
{
    $ip = trim((string)getParameter('ip'));
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), _tr('Invalid IP address')));
        return;
    }
    $family = (strpos($ip, ':') !== false) ? 'inet6' : 'inet';
    $values = array(
        'traffic' => 'INPUT',
        'interface_in' => 'ANY',
        'interface_out' => null,
        'ip_source' => $ip,
        'mask_source' => ($family === 'inet6') ? '128' : '32',
        'ip_destin' => ($family === 'inet6') ? '::' : '0.0.0.0',
        'mask_destin' => '0',
        'protocol' => 'ALL',
        'port_in' => null,
        'port_out' => null,
        'type_icmp' => null,
        'id_ip' => null,
        'target' => 'ACCEPT',
        'state' => '',
        'geoipcountries' => array(),
        'geoipcontinents' => array(),
        'family' => $family,
        'priority' => 'normal',
    );
    $pRules = new paloSantoRules($pDB);
    $ok = $pRules->saveRule($values);
    if ($ok && $pRules->lastInsertId > 0) {
        $pRules->setDesactivated($pRules->lastInsertId);
    }
    issabelFwAudit::log('discovery.peer.rule.create', $ok ? 'ok' : 'error',
        array('ip' => $ip, 'msg' => $ok ? '' : $pRules->errMsg));
    $smarty->assign('SECURITY_ALERT', $ok
        ? issabel_alert_html('success', _tr('Message'), _tr('Rule created disabled. Review and enable it in Firewall Rules.'))
        : issabel_alert_html('error', _tr('ERROR'), $pRules->errMsg));
}
