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
    exec("/usr/bin/issabel-helper ssl_certbot renewcertificate", $result, $retorno);
    cert_json(array(
        'status' => $retorno == 0 ? 'success' : 'error',
        'result' => cert_output_html(implode("\n", $result)),
    ));
}

// Instalación de un certificado nuevo.
$staging = (isset($_POST['staging']) && $_POST['staging'] === '--test-cert') ? '--test-cert' : '';
$domain  = isset($_POST['domain']) ? trim((string)$_POST['domain']) : '';
$email   = isset($_POST['email'])  ? trim((string)$_POST['email'])  : '';

$errors = array();
$domainList = array();
foreach (explode(',', $domain) as $d) {
    $d = trim($d);
    if ($d === '') continue;
    if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$/', $d) || strpos($d, '..') !== false) {
        $errors['domain'] = _tr('The domain is invalid');
        break;
    }
    $domainList[] = $d;
}
if (count($domainList) === 0) {
    $errors['domain'] = sprintf(_tr('%s is required'), _tr('Domain'));
}
if ($email === '') {
    $errors['email'] = sprintf(_tr('%s is required'), _tr('Email'));
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = sprintf(_tr('%s is invalid'), _tr('Email'));
}

if (count($errors) > 0) {
    cert_json(array('status' => 'error', 'errors' => $errors));
}

$arrNetworkData['host'] = $domain;

// Check to see if we have ServerName defined in ssl.conf, if not, add it
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
    exec("/usr/bin/issabel-helper ssl_certbot insertservername ".escapeshellarg($domain), $respuesta, $retorno);
} else {
    exec("/usr/bin/issabel-helper ssl_certbot editservername ".escapeshellarg($domain), $respuesta, $retorno);
}

exec("/usr/bin/issabel-helper ssl_certbot newcertificate ".escapeshellarg($email)." ".escapeshellarg($domain)." $staging", $result, $retorno);
$resultText = implode("\n", $result);

if($retorno != 0) {
    cert_json(array(
        'status' => 'error',
        'domain' => '',
        'email'  => '',
        'result' => cert_output_html($resultText),
    ));
}

exec("/usr/bin/issabel-helper ssl_certbot writevars ".escapeshellarg($email)." ".escapeshellarg($domain), $out, $rtn);
$pNet->escribir_configuracion_red_sistema($arrNetworkData);

cert_json(array(
    'status' => 'success',
    'domain' => $domain,
    'email'  => $email,
    'result' => cert_output_html($resultText),
));
