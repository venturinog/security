<?php
/*
 * sec_responsive — Responsive Firewall simple (PJSIP, toggle default off).
 */

include_once "libs/paloSantoForm.class.php";
include_once "modules/sec_rules/libs/issabelFwAudit.class.php";

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
    if ($action === 'save') {
        saveSettings($smarty, $pDB);
    } elseif ($action === 'on' || $action === 'off') {
        toggleResponsive($smarty, $action);
    } elseif ($action === 'flush') {
        flushResponsive($smarty);
    }

    return renderPage($smarty, $module_name, $local_templates_dir, $pDB);
}

function settings(&$pDB)
{
    $row = $pDB->getFirstRowQuery("SELECT * FROM responsive WHERE id=1", true);
    if (!is_array($row)) {
        $row = array('enabled' => 0, 'allow_timeout' => 7200, 'sip_port' => 5060,
            'rtp_start' => 10000, 'rtp_end' => 20000, 'probe_rate' => 6, 'probe_burst' => 3);
    }
    return $row;
}

function allowedIps()
{
    $ips = array();
    foreach (array('issabel_responsive', 'issabel_responsive6') as $set) {
        $out = shell_exec('/usr/sbin/ipset list '.$set.' 2>/dev/null');
        if (!is_string($out)) continue;
        $inMembers = false;
        foreach (explode("\n", $out) as $line) {
            $line = trim($line);
            if ($line === 'Members:') { $inMembers = true; continue; }
            if (!$inMembers || $line === '') continue;
            $parts = preg_split('/\s+/', $line);
            $ips[] = array('set' => $set, 'ip' => $parts[0], 'timeout' => isset($parts[2]) ? $parts[2] : '');
        }
    }
    return $ips;
}

function renderPage($smarty, $module_name, $local_templates_dir, &$pDB)
{
    $s = settings($pDB);
    $smarty->assign('SETTINGS', $s);
    $smarty->assign('ALLOWED', allowedIps());
    $smarty->assign('SERVICE', trim((string)shell_exec('/usr/bin/systemctl is-active issabel-fwresponsive 2>/dev/null')));
    $smarty->assign('MODULE', $module_name);
    $smarty->assign('CSRF_FIELD', issabel_csrf_field());
    $smarty->assign('TITLE', _tr('Responsive Firewall'));
    $smarty->assign('ENABLED', !empty($s['enabled']));
    $smarty->assign('ON', _tr('Enable'));
    $smarty->assign('OFF', _tr('Disable'));
    $smarty->assign('SAVE', _tr('Save'));
    $smarty->assign('FLUSH', _tr('Flush allowed IPs'));
    $smarty->assign('ALLOW_TIMEOUT', _tr('Allow timeout (seconds)'));
    $smarty->assign('SIP_PORT', _tr('SIP port (UDP)'));
    $smarty->assign('RTP_START', _tr('RTP start'));
    $smarty->assign('RTP_END', _tr('RTP end'));
    $smarty->assign('PROBE_RATE', _tr('Probation packets/minute'));
    $smarty->assign('PROBE_BURST', _tr('Probation burst'));
    $smarty->assign('ALLOWED_IPS', _tr('Allowed IPs'));
    $smarty->assign('NO_IPS', _tr('No IPs are currently allowed'));
    $smarty->assign('STATUS', _tr('Service'));
    $smarty->assign('WARNING', _tr('The responsive firewall is a SIP/PJSIP UDP feature. It does not open SSH, HTTPS or the web interface.'));
    $smarty->assign('SAVED', _tr('Settings saved'));
    $smarty->assign('ERROR', _tr('ERROR'));
    $smarty->assign('MESSAGE', _tr('Message'));

    return $smarty->fetch("$local_templates_dir/responsive.tpl");
}

function saveSettings($smarty, &$pDB)
{
    $timeout = (int)getParameter('allow_timeout');
    $sip = (int)getParameter('sip_port');
    $rtpStart = (int)getParameter('rtp_start');
    $rtpEnd = (int)getParameter('rtp_end');
    $rate = (int)getParameter('probe_rate');
    $burst = (int)getParameter('probe_burst');

    if ($timeout < 60 || $sip < 1 || $sip > 65535 || $rtpStart < 1 || $rtpEnd > 65535
        || $rtpEnd < $rtpStart || $rate < 1 || $burst < 1) {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), _tr('Invalid responsive settings')));
        return;
    }

    $ok = $pDB->genQuery("UPDATE responsive SET allow_timeout=?, sip_port=?, rtp_start=?, rtp_end=?, probe_rate=?, probe_burst=? WHERE id=1",
        array($timeout, $sip, $rtpStart, $rtpEnd, $rate, $burst));
    issabelFwAudit::log('responsive.settings', $ok ? 'ok' : 'error',
        array('timeout' => $timeout, 'sip' => $sip, 'rtp' => $rtpStart.':'.$rtpEnd));

    if ($ok) {
        $s = settings($pDB);
        if (!empty($s['enabled'])) {
            exec('/usr/bin/issabel-helper fwconfig --responsive apply 2>&1', $out, $ret);
            if ($ret != 0) {
                $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), implode(' ', $out)));
                return;
            }
        }
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('success', _tr('Message'), _tr('Settings saved')));
    } else {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), $pDB->errMsg));
    }
}

function toggleResponsive($smarty, $action)
{
    $cmd = ($action === 'on') ? 'on' : 'off';
    exec('/usr/bin/issabel-helper fwconfig --responsive '.$cmd.' 2>&1', $out, $ret);
    issabelFwAudit::log('responsive.'.($cmd === 'on' ? 'enable' : 'disable'), $ret == 0 ? 'ok' : 'error',
        array('msg' => $ret == 0 ? '' : implode(' ', $out)));
    if ($ret == 0) {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('success', _tr('Message'),
            $cmd === 'on' ? _tr('Responsive firewall enabled') : _tr('Responsive firewall disabled')));
    } else {
        $smarty->assign('SECURITY_ALERT', issabel_alert_html('error', _tr('ERROR'), implode(' ', $out)));
    }
}

function flushResponsive($smarty)
{
    exec('/usr/bin/issabel-helper fwconfig --responsive flush 2>&1', $out, $ret);
    issabelFwAudit::log('responsive.flush', $ret == 0 ? 'ok' : 'error',
        array('msg' => $ret == 0 ? '' : implode(' ', $out)));
    $smarty->assign('SECURITY_ALERT', $ret == 0
        ? issabel_alert_html('success', _tr('Message'), _tr('Allowed IPs flushed'))
        : issabel_alert_html('error', _tr('ERROR'), implode(' ', $out)));
}
