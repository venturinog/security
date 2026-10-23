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
  | The Initial Developer of the Original Code is Issabel Foundation     |
  +----------------------------------------------------------------------+
  $Id: index.php, Thu 13 May 2021 06:31:37 PM EDT, nicolas@issabel.com
  */

function _moduleContent(&$smarty, $module_name) {
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/issabelAttacks.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pDB     = new paloDB($arrConf['dsn_conn_database']);
    $pAttack = new issabelAttacks($pDB);

    $action = getAction();
    if($action=="") {
        return viewGeoIP_MAP($smarty, $module_name, $local_templates_dir, $arrConf);
    }

    // Acciones que cambian estado: sólo POST (y por tanto con token CSRF).
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    switch($action) {
        case 'block':
        case 'unblock':
        case 'noneblock':
            if (!$isPost) {
                http_response_code(405);
                return json_encode(array('status' => 'error', 'message' => _tr('Method not allowed')));
            }
            $pais = strtoupper(trim((string)getParameter($action)));
            if (!preg_match('/^[A-Z]{2}$/', $pais)) {
                http_response_code(400);
                return json_encode(array('status' => 'error', 'message' => _tr('Invalid country code')));
            }
            if ($action === 'block')      $sComando = '/usr/bin/issabel-helper fwconfig --block_country='.escapeshellarg($pais);
            elseif ($action === 'unblock') $sComando = '/usr/bin/issabel-helper fwconfig --whitelist_country='.escapeshellarg($pais);
            else                           $sComando = '/usr/bin/issabel-helper fwconfig --none_country='.escapeshellarg($pais);
            exec($sComando.' 2>&1', $output, $ret);
            if ($ret == 0) {
                $message = ($action === 'block') ? _tr('Country blocked')
                         : (($action === 'unblock') ? _tr('Country whitelisted') : _tr('Country removed from the list'));
            } else {
                $message = trim(implode("\n", $output));
                if ($message === '') $message = _tr('The change could not be applied');
            }
            return json_encode(array(
                'status' => $ret == 0 ? 'success' : 'error',
                'message' => $message,
                'iso' => $pais,
            ));

        case 'f2b':
            $output = $ret = NULL;
            exec('/usr/bin/issabel-helper fwconfig --listfail2banblocked', $output, $ret);
            return isset($output[0]) ? $output[0] : '{}';

        case 'attacks':
            header('Content-Type: application/json; charset=utf-8');
            return json_encode($pAttack->getAttackCounts(1440));

        case 'geoipstatus':
            header('Content-Type: application/json; charset=utf-8');
            $output = $ret = NULL;
            exec('/usr/bin/issabel-helper fwconfig --isactivegeoip 2>&1', $output, $ret);
            return json_encode(array(
                'available' => ($ret == 0),
                'message'   => trim(implode("\n", $output)),
            ));

        case 'homecountry':
            $output = $ret = NULL;
            exec('/usr/bin/issabel-helper fwconfig --homecountry', $output, $ret);
            return isset($output[0]) ? $output[0] : '';

        case 'pass':
            $output = $ret = NULL;
            exec('/usr/bin/issabel-helper fwconfig --listgeopass', $output, $ret);
            return isset($output[0]) ? $output[0] : '';

        case 'blocked':
            $output = $ret = NULL;
            exec('/usr/bin/issabel-helper fwconfig --listgeoblocked', $output, $ret);
            return isset($output[0]) ? $output[0] : '';

        default:
            return '';
    }
}

function viewGeoIP_MAP($smarty, $module_name, $local_templates_dir, $arrConf) {
    $lang = get_language();
    $smarty->assign("MAP_LANG", bcp47Language($lang));
    $smarty->assign("MAP_TITLE", _tr("GeoIP Map"));
    $smarty->assign("MAP_HELP", _tr("Click a country to cycle between blocked, whitelisted and not listed."));
    $smarty->assign("LEGEND_BLOCKED", _tr("Blocked"));
    $smarty->assign("LEGEND_PASS", _tr("Whitelisted"));
    $smarty->assign("LEGEND_NONE", _tr("Not listed"));
    $smarty->assign("LEGEND_HOME", _tr("This server"));
    $smarty->assign("REFRESH", _tr("Refresh"));
    $smarty->assign("BLOCKED_TITLE", _tr("Blocked countries"));
    $smarty->assign("PASS_TITLE", _tr("Whitelisted countries"));
    $smarty->assign("CHART_TITLE", _tr("Attacks by country (last 24 hours)"));
    $smarty->assign("NO_DATA", _tr("No data"));
    $smarty->assign("MAP_ERROR", _tr("The map could not be loaded"));
    $smarty->assign("ATTACKS_TITLE", _tr("Recent attacks"));

    $geoOut = $geoRet = NULL;
    exec('/usr/bin/issabel-helper fwconfig --isactivegeoip 2>&1', $geoOut, $geoRet);
    $smarty->assign("GEOIP_AVAILABLE", $geoRet == 0 ? 'yes' : 'no');
    $smarty->assign("GEOIP_ERROR", trim(implode("\n", $geoOut)));
    $smarty->assign("GEOIP_UNAVAILABLE_TITLE", _tr("GeoIP is not available"));

    $svgFile = "$local_templates_dir/img/world.svg";
    $smarty->assign("WORLD_SVG", is_readable($svgFile) ? file_get_contents($svgFile) : '');

    return $smarty->fetch("$local_templates_dir/map.tpl");
}

/**
 * Código de idioma para Intl.DisplayNames (nombres de país localizados).
 */
function bcp47Language($lang)
{
    $map = array(
        'br' => 'pt-BR', 'cn' => 'zh-CN', 'pt-br' => 'pt-BR', 'uk' => 'uk',
        'fa' => 'fa', 'ru' => 'ru', 'es' => 'es', 'fr' => 'fr', 'de' => 'de',
        'it' => 'it', 'ja' => 'ja', 'ko' => 'ko', 'el' => 'el', 'hu' => 'hu',
        'hr' => 'hr', 'nl' => 'nl', 'no' => 'no', 'pl' => 'pl', 'ro' => 'ro',
        'sl' => 'sl', 'sr' => 'sr', 'sv' => 'sv', 'tr' => 'tr', 'ca' => 'ca',
        'bg' => 'bg', 'da' => 'da',
    );
    return isset($map[$lang]) ? $map[$lang] : 'en';
}

function getAction() {
    if(getParameter("block")) return "block";
    else if(getParameter("unblock")) return "unblock";
    else if(getParameter("noneblock")) return "noneblock";
    else if(getParameter("f2b")) return "f2b";
    else if(getParameter("homecountry")) return "homecountry";
    else if(getParameter("pass")) return "pass";
    else if(getParameter("blocked")) return "blocked";
    else if(getParameter("attacks")) return "attacks";
    else if(getParameter("geoipstatus")) return "geoipstatus";
    else return "";
}

?>
