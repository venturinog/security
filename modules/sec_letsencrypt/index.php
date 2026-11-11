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

define('LE_REGISTRY', '/usr/share/issabel/letsencrypt/providers.php');
define('LE_VALUES', '/etc/letsencrypt/values');
define('LE_HOOKS_DIR', '/etc/letsencrypt/hooks');
define('LE_HOOKS_MANIFEST', LE_HOOKS_DIR.'/hooks.json');

function cert_methods()
{
    if (!is_readable(LE_REGISTRY)) {
        return array();
    }
    $registry = include LE_REGISTRY;
    return is_array($registry) ? $registry : array();
}

/*
 * Custom DNS hooks are declared in a root-owned JSON manifest. The GUI only
 * reads the labels; the privileged helper re-reads the manifest and validates
 * the script before running it, so this list is convenience, not trust.
 */
function cert_hooks()
{
    $hooks = array();
    if (!is_readable(LE_HOOKS_MANIFEST)) {
        return $hooks;
    }
    $data = json_decode((string)file_get_contents(LE_HOOKS_MANIFEST), true);
    if (!is_array($data)) {
        return $hooks;
    }
    foreach ($data as $hookId => $hook) {
        if (!is_string($hookId) || !preg_match('/^[a-z0-9][a-z0-9._-]{0,63}$/', $hookId)) {
            continue;
        }
        if (!is_array($hook)) {
            continue;
        }
        $label = (isset($hook['label']) && $hook['label'] !== '') ? (string)$hook['label'] : $hookId;
        $description = isset($hook['description']) ? (string)$hook['description'] : '';
        $propagation = isset($hook['propagation_default']) ? (int)$hook['propagation_default'] : 30;
        if ($propagation < 5 || $propagation > 300) {
            $propagation = 30;
        }
        $hooks[$hookId] = array(
            'label'               => $label,
            'description'         => $description,
            'propagation_default' => $propagation,
        );
    }
    return $hooks;
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

    // Stored configuration: current method, provider and certificate data.
    $values = cert_values();
    $arrRegistry = cert_methods();
    if ($values['domain'] !== '') {
        $smarty->assign("valuedomain", $values['domain']);
    }

    // Translate the method registry for the template.
    $methods = array();
    foreach ($arrRegistry as $methodId => $method) {
        if (!empty($method['hook'])) {
            // Custom hooks are added from the manifest below.
            continue;
        }
        $fields = array();
        foreach ($method['fields'] as $field) {
            $options = array();
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    $options[] = array('VALUE' => $option['value'], 'LABEL' => $option['label']);
                }
            }
            $fields[] = array(
                'NAME'        => $field['name'],
                'TYPE'        => $field['type'],
                'LABEL'       => _tr($field['label']),
                'HELP'        => _tr($field['help']),
                'PLACEHOLDER' => _tr($field['placeholder']),
                'REQUIRED'    => !empty($field['required']),
                'DEFAULT'     => isset($field['default']) ? $field['default'] : '',
                'MIN'         => isset($field['min']) ? $field['min'] : '',
                'MAX'         => isset($field['max']) ? $field['max'] : '',
                'MAXLENGTH'   => isset($field['maxlength']) ? $field['maxlength'] : '',
                'OPTIONS'     => $options,
                'ADVANCED'    => !empty($field['advanced']),
            );
        }
        $methods[$methodId] = array(
            'ID'                  => $methodId,
            'LABEL'               => _tr($method['label']),
            'SUMMARY'             => _tr($method['summary']),
            'GROUP'               => $method['group'],
            'DOCS_URL'            => $method['docs_url'],
            'PROPAGATION_DEFAULT' => (int)$method['propagation_default'],
            'CREDENTIALS_PROVIDER' => $method['credentials_provider'],
            'FIELDS'              => $fields,
            'HOOK'                => false,
        );
    }

    // Custom DNS hooks installed by the administrator. They have no credential
    // fields: the script reads its own configuration from the hooks directory.
    foreach (cert_hooks() as $hookId => $hook) {
        $hookMethodId = 'dns-hook:'.$hookId;
        $methods[$hookMethodId] = array(
            'ID'                  => $hookMethodId,
            'LABEL'               => $hook['label'],
            'SUMMARY'             => $hook['description'],
            'GROUP'               => 'dns',
            'DOCS_URL'            => '',
            'PROPAGATION_DEFAULT' => (int)$hook['propagation_default'],
            'CREDENTIALS_PROVIDER' => '',
            'FIELDS'              => array(),
            'HOOK'                => true,
        );
    }

    // A stored method may be a hook that has since been removed from the
    // manifest; in that case fall back to webroot.
    $currentMethod = 'webroot';
    if ($values['method'] !== '' && isset($methods[$values['method']])) {
        $currentMethod = $values['method'];
    }

    $hasCert = ($values['domain'] !== '' || $values['email'] !== '');

    $smarty->assign("METHODS",           $methods);
    $smarty->assign("CURRENT_METHOD",    $currentMethod);
    $smarty->assign("CURRENT_SUMMARY",   isset($methods[$currentMethod]) ? $methods[$currentMethod]['SUMMARY'] : '');
    $smarty->assign("CURRENT_GROUP",     isset($methods[$currentMethod]) ? $methods[$currentMethod]['GROUP'] : 'http');
    $smarty->assign("CURRENT_PROPAGATION", isset($methods[$currentMethod]) ? $methods[$currentMethod]['PROPAGATION_DEFAULT'] : 0);
    $smarty->assign("CREDENTIALS_PROVIDER", $values['credentials']);
    $smarty->assign("HAS_CERT",          $hasCert);
    $smarty->assign("valueemail",        $values['email']);

    $smarty->assign("INSTALLNEW", _tr("Install New Certificate from Let's Encrypt"));
    $smarty->assign("INSTALL",    _tr("Install"));
    $smarty->assign("RENEW",      _tr("Renew"));
    $smarty->assign("TEST",       _tr("Test Validation"));
    $smarty->assign("REISSUE",    _tr("Reissue Certificate"));
    $smarty->assign("REISSUE_CONFIRM", _tr("This requests a new certificate and consumes a Let's Encrypt rate limit slot. Continue?"));
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

    $smarty->assign("VALIDATION_METHOD", _tr("Validation method"));
    $smarty->assign("PROVIDER_DOCS",    _tr("Open the provider documentation"));
    $smarty->assign("CREDENTIALS_NOTE", _tr("Credentials are stored in /etc/letsencrypt/credentials and are readable only by root."));
    $smarty->assign("CREDENTIALS_STORED", _tr("Credentials already stored. Leave the fields blank to keep them."));
    $smarty->assign("PROPAGATION_LABEL", _tr("Propagation Time (seconds)"));
    $smarty->assign("PROPAGATION_HELP", _tr("Seconds to wait for the TXT record to become visible before the certificate authority checks it. Increase it to 30-60 if validation fails."));
    $smarty->assign("WILDCARD_HINT", _tr("Wildcard names such as *.example.com are only accepted with a DNS-01 method."));
    $smarty->assign("HOOK_NOTE", _tr("This method runs the hook script installed by the administrator in /etc/letsencrypt/hooks. The script receives the domain and the validation value, publishes the TXT record and removes it on cleanup. It reads its own credentials from that directory."));

    $smarty->assign("STEPS_HTTP", array(
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

    $smarty->assign("STEPS_DNS", array(
        _tr("1.- Create a Valid Domain by purchasing it with you preferred Vendor or get one from free DDNS service like No-Ip.org"),
        _tr("2.- Create the DNS API credentials in the provider panel and paste them in the credential fields."),
        _tr("3.- Make sure the domain is hosted by that provider: the validation TXT record is created there."),
        _tr("4.- In the field DOMAIN enter your domain. Wildcard names such as *.example.com are allowed, comma separated."),
        _tr("5.- In the field EMAIL enter your email to register your account."),
        _tr("6.- Use Test Validation to check the credentials and the zone without issuing a certificate."),
        _tr("7.- Press Install to issue the certificate. TCP port 80 does not need to be open."),
        _tr("8.- Press Renew to renew it. Renewal also runs automatically from cron."),
    ));

    $smarty->assign("STEP_NOTE", _tr("Visit https://letsencrypt.org to know more about the free certification process, their TOS and License Agreement. This is a basic GUI for the APACHE CERTBOT and is provided as is and without warranty. If you have any questions please provide it in the forum. Don't forget to donate to the Let's Encrypt Project."));

    $contenidoModulo = $smarty->fetch("$local_templates_dir/new.tpl");

    return $contenidoModulo;
}

function isValidIP($str) {
    return (bool)preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $str);
}
