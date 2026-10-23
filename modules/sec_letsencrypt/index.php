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
require_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

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

    if(isValidIP($_SERVER['HTTP_HOST'])) {
        $smarty->assign("NO_HOSTNAME_NOTICE", _tr("Please be sure to access the Web UI via a valid name configured in your DNS. That would be the same domain you will pass to Let's Encrypt to validate the SSL certificate. You should not access the site via IP address."));
    } else {
        $smarty->assign("NO_HOSTNAME_NOTICE", "");
    }

    $sHostname = trim(preg_replace('/\s\s+/', ' ', (string)@file_get_contents("/etc/hostname")));
    $smarty->assign("valuedomain", $sHostname);

    // There is no ServerName in ssl.conf, let's add it, otherwise certbot won't be able to do its magic
    if($modify==0 && $sHostname !== '') {
        exec("/usr/bin/issabel-helper ssl_certbot insertservername ".escapeshellarg($sHostname), $respuesta, $retorno);
    }

    // Check if we already have a letsencrypt certificate
    $hasCert = false;
    $valueemail = '';
    $valuesc="/etc/letsencrypt/values";
    if ( file_exists($valuesc) ) {
        $ffvalues = file_get_contents($valuesc);
        if (preg_match("/email=(.*)/i",$ffvalues,$email1) && isset($email1[1])) {
            $valueemail = trim($email1[1]);
        }
        if (preg_match("/domain=(.*)/i",$ffvalues,$domain2) && isset($domain2[1])) {
            $smarty->assign("valuedomain", trim($domain2[1]));
            $hasCert = true;
        }
        if ($valueemail !== '') $hasCert = true;
    }

    $smarty->assign("HAS_CERT", $hasCert);
    $smarty->assign("valueemail", $valueemail);

    $smarty->assign("INSTALLNEW", _tr("Install New Certificate from Let's Encrypt"));
    $smarty->assign("INSTALL",    _tr("Install"));
    $smarty->assign("RENEW",      _tr("Renew"));
    $smarty->assign("USAGE",      _tr("Usage"));
    $smarty->assign("RENEWCERT",  _tr("Renew Certificate"));
    $smarty->assign("HASDATA",    _tr("This is your actual account and domain configuration"));
    $smarty->assign("DOMAIN",     _tr("Domain"));
    $smarty->assign("EMAIL",      _tr("Email"));
    $smarty->assign("STAGING",    _tr("Staging Certificate (use this for testing)"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CERT_RESULT_TITLE", _tr("Output log"));
    $smarty->assign("CERT_ERROR", _tr("The request could not be completed"));
    $smarty->assign("PROCESSING", _tr("Processing"));
    $smarty->assign("STEPS", array(
        _tr("1.- Create a Valid Domain by purchasing it with you preferred Vendor or get one from free DDNS service like No-Ip.org"),
        _tr("2.- Be sure that you domain is redirected to your PBX Service."),
        _tr("3.- Open the 443 and 80 ports in your firewall and redirect to your PBX."),
        _tr("4.- In the field DOMAIN enter your domain to obtain a valid certificate. You can add many domains comma separated"),
        _tr("5.- In the field EMAIL enter your email to register your account."),
        _tr("6.- To renew your certificate press the Renew button."),
        _tr("7.- For test certificates enable the Staging checkbox."),
        _tr("8.- After the process finish, reload your window with the Domain name (make sure the DNS is set internally too)."),
        _tr("9.- You can enable the firewall rules again for ports 443 and 80."),
        _tr("10.- Enjoy."),
    ));
    $smarty->assign("STEP_NOTE", _tr("Visit https://letsencrypt.org to know more about the free certification process, their TOS and License Agreement. This is a basic GUI for the APACHE CERTBOT and is provided as is and without warranty. If you have any questions please provide it in the forum. Don't forget to donate to the Let's Encrypt Project."));

    $contenidoModulo = $smarty->fetch("$local_templates_dir/new.tpl");

    return $contenidoModulo;
}

function isValidIP($str) {
    return (bool)preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $str);
}
