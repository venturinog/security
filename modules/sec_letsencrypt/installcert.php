<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
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
include("/var/www/html/libs/misc.lib.php");
include("/var/www/html/configs/default.conf.php");
include("/var/www/html/libs/paloSantoACL.class.php");
include_once("/var/www/html/libs/paloSantoDB.class.php");
include_once("/var/www/html/libs/paloSantoNetwork.class.php");

session_name("issabelSession");
session_start();

$pDB  = new paloDB($arrConf["issabel_dsn"]["acl"]);
$pACL = new paloACL($pDB);
$issabel_user = isset($_SESSION["issabel_user"]) ? $_SESSION["issabel_user"] : "";

session_commit();

if(!$pACL->isUserAdministratorGroup($issabel_user)){
    http_response_code(403);
    die();
}

// Este endpoint vive fuera del index.php del framework, así que el guard
// global no se ejecuta solo. csrf.js añade la cabecera X-Issabel-CSRF-Token a
// todo AJAX de jQuery; en modo enforce un POST sin token responde 403.
issabel_csrf_guard();

// Este script no se ejecuta desde el index.php del framework: las rutas
// relativas de load_language_module() no aplican. Se carga el idioma con
// rutas absolutas y se conserva el inglés como respaldo.
global $arrLang, $arrLangModule;
$arrLang = array();
$lang = get_language('/var/www/html/');
include_once '/var/www/html/modules/sec_letsencrypt/lang/en.lang';
$arrLangEN = isset($arrLangModule) ? $arrLangModule : array();
if ($lang !== 'en') {
    $langFile = '/var/www/html/modules/sec_letsencrypt/lang/'.$lang.'.lang';
    if (is_readable($langFile)) {
        include_once $langFile;
        $arrLangModule = array_merge($arrLangEN, $arrLangModule);
    }
}
$arrLang = array_merge($arrLang, $arrLangModule);

header('Content-Type: application/json; charset=utf-8');

define('LE_REGISTRY', '/usr/share/issabel/letsencrypt/providers.php');
define('LE_VALUES', '/etc/letsencrypt/values');

/**
 * Devuelve el log de certbot listo para insertar en el DOM, escapado.
 */
function cert_output_html($result)
{
    return '<strong>'._tr('Output log').':</strong><br><pre>'
         . nl2br(htmlspecialchars((string)$result, ENT_COMPAT, 'UTF-8'))
         . '</pre>';
}

function cert_json($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
}

function cert_registry()
{
    if (!is_readable(LE_REGISTRY)) {
        return array();
    }
    $registry = include LE_REGISTRY;
    return is_array($registry) ? $registry : array();
}

function cert_values()
{
    $values = array('domain' => '', 'email' => '', 'method' => '', 'credentials' => '', 'propagation' => '', 'cert_name' => '');
    if (!is_readable(LE_VALUES)) {
        return $values;
    }
    foreach (file(LE_VALUES) as $line) {
        if (preg_match('/^([a-z_]+)=(.*)$/', trim($line), $m)) {
            $values[$m[1]] = $m[2];
        }
    }
    return $values;
}

/**
 * Ejecuta /usr/bin/issabel-helper con un argv sin shell y devuelve
 * array(exit_code, output). El helper corre como root vía sudo. El segundo
 * parámetro es el payload ya codificado que se envía por STDIN.
 */
function cert_helper_exec($cmd, $stdinPayload = null)
{
    $descriptors = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('redirect', 1),
    );
    $process = @proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($process)) {
        return array(1, 'Could not execute the privileged helper');
    }
    if ($stdinPayload !== null) {
        fwrite($pipes[0], $stdinPayload);
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);
    return array($code, trim((string)$output));
}

function cert_helper($command, $params, $stdin = null)
{
    $paramsJson = json_encode($params);
    if ($paramsJson === false) {
        return array(1, 'Could not encode the request parameters');
    }
    $stdinJson = null;
    if ($stdin !== null) {
        $stdinJson = json_encode($stdin);
        if ($stdinJson === false) {
            return array(1, 'Could not encode the credentials');
        }
    }
    $cmd = array('/usr/bin/issabel-helper', 'ssl_certbot', $command, $paramsJson);
    return cert_helper_exec($cmd, $stdinJson);
}

/**
 * Valida y normaliza un dominio. Devuelve '' si no es válido.
 */
function cert_check_domain($domain, $allowWildcard)
{
    $domain = strtolower(trim((string)$domain));
    if ($domain === '') {
        return '';
    }
    $wildcard = false;
    if (strpos($domain, '*.') === 0) {
        $wildcard = true;
        $domain = substr($domain, 2);
    }
    if ($wildcard && !$allowWildcard) {
        return '';
    }
    if (strlen($domain) > 253 || strpos($domain, '..') !== false) {
        return '';
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/', $domain)) {
        return '';
    }
    return ($wildcard ? '*.' : '').$domain;
}

$arrRegistry = cert_registry();
if (count($arrRegistry) === 0) {
    cert_json(array('status' => 'error', 'result' => cert_output_html('The validation method registry is missing')));
}

$pNet = new paloNetwork();
$arrNetwork = $pNet->obtener_configuracion_red();
$arrNetworkData = array('dns_ip_1' => '', 'dns_ip2' => '', 'host' => '', 'gateway_ip' => '');
if(is_array($arrNetwork)) {
    $arrNetworkData['dns_ip_1'] = isset($arrNetwork['dns'][0])?$arrNetwork['dns'][0]:'';
    $arrNetworkData['dns_ip2'] = isset($arrNetwork['dns'][1])?$arrNetwork['dns'][1]:'';
    $arrNetworkData['host'] = isset($arrNetwork['host'])?$arrNetwork['host']:'';
    $arrNetworkData['gateway_ip'] = isset($arrNetwork['gateway'])?$arrNetwork['gateway']:'';
}

// Renovación: no requiere dominio ni email, usa la configuración guardada.
if(isset($_POST['renew'])) {
    list($retorno, $result) = cert_helper('renewcertificate', array());
    cert_json(array(
        'status' => $retorno == 0 ? 'success' : 'error',
        'result' => cert_output_html($result),
    ));
}

// Instalación de un certificado nuevo o prueba de validación.
$action = isset($_POST['test']) ? 'test' : 'install';

$methodKey = isset($_POST['method']) ? (string)$_POST['method'] : 'webroot';
if (strpos($methodKey, 'dns-hook:') === 0) {
    // Custom DNS hook: the helper resolves and validates the script against the
    // root-owned allowlist. Here only the template entry is needed.
    if (!isset($arrRegistry['dns-hook'])) {
        cert_json(array('status' => 'error', 'result' => cert_output_html('Custom DNS hooks are not supported')));
    }
    $method = $arrRegistry['dns-hook'];
} else {
    if (!isset($arrRegistry[$methodKey])) {
        cert_json(array('status' => 'error', 'result' => cert_output_html('Unknown validation method')));
    }
    $method = $arrRegistry[$methodKey];
}
$isDns = ($method['group'] === 'dns');

$domainRaw = isset($_POST['domain']) ? trim((string)$_POST['domain']) : '';
$email     = isset($_POST['email'])  ? trim((string)$_POST['email'])  : '';
$staging   = (isset($_POST['staging']) && $_POST['staging'] === '--test-cert');
$reissue   = (isset($_POST['reissue']) && $_POST['reissue'] === '1');

$errors = array();
$domainList = array();
$primaryDomain = '';
foreach (explode(',', $domainRaw) as $d) {
    if (trim((string)$d) === '') {
        continue;
    }
    $checked = cert_check_domain($d, $isDns);
    if ($checked === '') {
        $errors['domain'] = _tr('The domain is invalid');
        break;
    }
    $domainList[] = $checked;
    if ($primaryDomain === '' && strpos($checked, '*.') !== 0) {
        $primaryDomain = $checked;
    }
}
if (count($domainList) === 0 && !isset($errors['domain'])) {
    $errors['domain'] = sprintf(_tr('%s is required'), _tr('Domain'));
}
if ($primaryDomain === '' && count($domainList) > 0) {
    // Wildcard-only certificate: use the base domain for ServerName and the
    // certificate lineage name, without the wildcard label.
    $primaryDomain = str_replace('*.', '', $domainList[0]);
}
if ($email === '') {
    $errors['email'] = sprintf(_tr('%s is required'), _tr('Email'));
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = sprintf(_tr('%s is invalid'), _tr('Email'));
}

$propagation = isset($_POST['propagation']) ? (int)$_POST['propagation'] : (int)$method['propagation_default'];
if ($isDns && ($propagation < 5 || $propagation > 300)) {
    $errors['propagation'] = sprintf(_tr('%s is invalid'), _tr('Propagation Time (seconds)'));
}
if (!$isDns) {
    $propagation = 0;
}

// Credenciales del método. Se envían al helper por STDIN; aquí sólo se
// validan para dar un mensaje por campo antes de invocarlo.
$credentials = array();
$credentialsProvided = false;
foreach ($method['fields'] as $field) {
    $name = $field['name'];
    $value = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
    if ($value !== '') {
        $credentialsProvided = true;
        $credentials[$name] = $value;
    }
    if (isset($field['maxlength']) && strlen($value) > $field['maxlength']) {
        $errors[$name] = sprintf(_tr('%s is invalid'), _tr($field['label']));
    }
    if ($field['type'] === 'number' && $value !== '' && !preg_match('/^\d+$/', $value)) {
        $errors[$name] = sprintf(_tr('%s is invalid'), _tr($field['label']));
    }
}

$storedValues = cert_values();
if ($isDns && !$credentialsProvided) {
    if ($storedValues['credentials'] !== $method['credentials_provider']) {
        foreach ($method['fields'] as $field) {
            if (!empty($field['required'])) {
                $errors[$field['name']] = sprintf(_tr('%s is required'), _tr($field['label']));
            }
        }
    }
} elseif ($isDns && $credentialsProvided) {
    // If any credential was typed, require the complete set so a partial form
    // is not written over the stored file.
    foreach ($method['fields'] as $field) {
        $name = $field['name'];
        if (!empty($field['required']) && (!isset($credentials[$name]) || $credentials[$name] === '')) {
            $errors[$name] = sprintf(_tr('%s is required'), _tr($field['label']));
        }
    }
}

if (count($errors) > 0) {
    cert_json(array('status' => 'error', 'errors' => $errors));
}

$params = array(
    'method'      => $methodKey,
    'domains'     => $domainList,
    'email'       => $email,
    'staging'     => $staging,
    'reissue'     => $reissue,
    'propagation' => $propagation,
    'webroot'     => isset($method['webroot']) ? $method['webroot'] : '',
);

// Prueba de validación: certbot obtiene un certificado de staging con
// --dry-run, sin guardar nada y sin tocar Apache.
if ($action === 'test') {
    list($retorno, $result) = cert_helper('dryrun', $params, $credentials);
    cert_json(array(
        'status' => $retorno == 0 ? 'success' : 'error',
        'result' => cert_output_html($result),
    ));
}

// Ensure a ServerName exists in ssl.conf before certbot looks at Apache.
$modify=0;
$sslfile = "/etc/httpd/conf.d/ssl.conf";
if (is_readable($sslfile)) {
    foreach (file($sslfile) as $sLinea) {
        if (preg_match('/^[\s]*?ServerName\s*/', $sLinea)) {
            $modify=1;
        }
    }
}

if($modify==0) {
    cert_helper_exec(array('/usr/bin/issabel-helper', 'ssl_certbot', 'insertservername', $primaryDomain));
} else {
    cert_helper_exec(array('/usr/bin/issabel-helper', 'ssl_certbot', 'editservername', $primaryDomain));
}

list($retorno, $result) = cert_helper('newcertificate', $params, $credentials);

if($retorno != 0) {
    cert_json(array(
        'status' => 'error',
        'domain' => '',
        'email'  => '',
        'result' => cert_output_html($result),
    ));
}

$arrNetworkData['host'] = $primaryDomain;
$pNet->escribir_configuracion_red_sistema($arrNetworkData);

cert_json(array(
    'status' => 'success',
    'domain' => implode(',', $domainList),
    'email'  => $email,
    'method' => $methodKey,
    'result' => cert_output_html($result),
));
