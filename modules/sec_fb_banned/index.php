<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel Version 4.0.0                                                |
  +----------------------------------------------------------------------+
  | Copyright (c) 2017 Issabel Foundation                                |
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

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

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

    //conexion
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $accion = getAction();
    $content = "";
  
    switch($accion){
        case 'delete':
            $content = deleteBloqueados($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
         default:
            $content = reportBloqueados($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function fail2ban_rejected()
{

    $jails  = array('sshd','asterisk','apache-auth','issabel-gui');
    $allban = array();
    foreach($jails as $jail) {
        $respuesta = array();
        exec('/usr/bin/issabel-helper fb_client status '.$jail, $respuesta, $retorno);
        $output = implode(" ",$respuesta);
        $allban[$jail] = trim($output);
    }

    $id=0;    
    $rejected=array();
    foreach($allban as $jail=>$content) {
        if(strlen($content)){
            $allbanarr = explode(" ",$content);
            foreach ($allbanarr as $v) {
                $rejected[]=array("id"=>$id,"jail"=>$jail,"ip"=>$v);
                $id++;
            }
        }
    }
    
    return $rejected;
}

function reportBloqueados($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{

    $field_type = null;
    $field_pattern = null;
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);

    $oGrid->deleteList("Are you sure you wish to unban the record(s).?","delete",_tr("Unban"));

    $rejected=fail2ban_rejected();

    $totalBloqueados =  count($rejected);    
    $limit  = 20;
    $total  = $totalBloqueados;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->setTitle(_tr("Rejected IP"));
    $oGrid->setIcon("modules/$module_name/images/security_define_ports.png");
    $oGrid->pagingShow(true);
    $offset = $oGrid->calculateOffset();
    $url = array(
        "menu"         =>  $module_name,
        "filter_type"  =>  $field_type,
        "filter_txt"   =>  $field_pattern
    );
    $oGrid->setURL($url);

    $arrData = null;
    
    $arrResult = array_slice($rejected, $offset, ($totalBloqueados-$offset) < $limit ? ($totalBloqueados-$offset) : $limit);    
    $button_eliminar = "";
    $arrColumns = array($button_eliminar,_tr("Jail"),_tr("IP"),_tr("Country"));
    $oGrid->setColumns($arrColumns);
    if( is_array($arrResult) && $total>0 ){
        foreach($arrResult as $key => $value){
            $arrTmp[0] = "<input type='checkbox' name='".$value['id']."' id='".$value['id']."'>";
            $arrTmp[1] = $value['jail'];
            $arrTmp[2] = $value['ip'];
            $arrTmp[3] = _getCountryLabel($value['ip']);

            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    $contenidoModulo = $oGrid->fetchGrid();
    if (strpos($contenidoModulo, '<form') === FALSE)
        $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action=$url>$contenidoModulo</form>";
    //end grid parameters

    return $contenidoModulo;
}

/**
 * Clasifica una IP por rangos reservados (RFC 1918/5737/6598/3927 y CICNet).
 *
 * La geolocalización por IP se retiró en Issabel 6: la extensión PHP que la
 * resolvía (php-pecl-geoip) depende de libGeoIP, retirada upstream en 2019 y
 * sin reemplazo para PHP 8.4. Para los IPs rechazados por fail2ban el dato
 * útil es distinguir red privada de pública.
 *
 * @param  string $ip
 * @return string
 */
function _getCountryLabel($ip)
{
    if ( ! filter_var($ip, FILTER_VALIDATE_IP) ) {
        return "";
    }

    // ip2long() no maneja IPv6: se resuelve antes de convertir.
    if ( filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) {
        return _tr("IPv6 address");
    }

    $n = ip2long($ip);
    if ( $n === false ) {
        return "";
    }

    // Rango[CIDR, etiqueta].
    $ranges = array(
        array("0.0.0.0/8",        "Reserved"),
        array("10.0.0.0/8",       "Private network"),
        array("100.64.0.0/10",    "Carrier Grade NAT"),
        array("127.0.0.0/8",      "Loopback"),
        array("169.254.0.0/16",   "Link local"),
        array("172.16.0.0/12",    "Private network"),
        array("192.0.2.0/24",     "Documentation"),
        array("192.168.0.0/16",   "Private network"),
        array("198.18.0.0/15",    "Benchmarking"),
        array("198.51.100.0/24",  "Documentation"),
        array("203.0.113.0/24",   "Documentation"),
        array("224.0.0.0/4",      "Multicast"),
        array("240.0.0.0/4",      "Reserved"),
    );

    foreach ( $ranges as $r ) {
        list ( $net, $label ) = $r;
        list ( $netaddr, $prefix ) = explode( "/", $net );
        $mask = -1 << ( 32 - (int) $prefix );
        if ( ( $n & $mask ) === ( ip2long($netaddr) & $mask ) ) {
            return _tr($label);
        }
    }

    return _tr("Public address");
}

function deleteBloqueados($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $str_msj_error = "Error";

    $rejected=fail2ban_rejected();
    $error=0;
    foreach( $_POST as $key => $value ){
        if( $value == "on" ){
            $jail = strtolower($rejected[$key]["jail"]);
            $ip   = $rejected[$key]["ip"];    

            exec("/usr/bin/issabel-helper fb_client unban ".escapeshellarg($jail)." ".escapeshellarg($ip), $respuesta, $retorno);
            if($retorno==1) { $error=1; }
        }
    }
    if( $error == 0 ){
        $smarty->assign("mb_title", _tr("Message"));
        $smarty->assign("mb_message", _tr("Unbanned correctly"));
    }
    else{
        $smarty->assign("mb_title", _tr("ERROR"));
        $smarty->assign("mb_message", $str_msj_error);
    }

    return reportBloqueados($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function getAction()
{
    if(getParameter("delete")) //Get parameter by POST (submit)
        return "delete";
    else 
        return "report";
}
?>
