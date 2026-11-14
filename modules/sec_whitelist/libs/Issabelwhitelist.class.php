<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version {ISSBEL_VERSION}                                               |
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
  $Id: Issabelwhitelist.class.php, Thu 13 May 2021 06:27:28 PM EDT, nicolas@issabel.com
*/
class Issabelwhitelist{
    var $_DB;
    var $errMsg;

    function __construct(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /*HERE YOUR FUNCTIONS*/

    function getNumwhitelist()
    {

        $query   = "SELECT COUNT(*) FROM whitelist";

        $result=$this->_DB->getFirstRowQuery($query, false );

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getwhitelist($limit, $offset)
    {
        $where    = "";

        $query   = "SELECT * FROM whitelist $where LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true );

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getwhitelistByIp($ip_address)
    {
        $query = "SELECT * FROM whitelist WHERE ip_address=?";

        $result=$this->_DB->getFirstRowQuery($query, true, array("$ip_address"));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    function deletewhitelist($ip_address)
    {
        $query = "DELETE FROM whitelist WHERE ip_address=?";

        $result = $this->_DB->genQuery($query,array($ip_address));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }

        $sComando = '/usr/bin/issabel-helper fwconfig --remove_wl '.escapeshellarg($ip_address).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            return FALSE;
        }
        return TRUE;

    }

    function savewhitelist($ip_address,$note)
    {
        // La base guarda siempre la forma canónica (ver normalizeEntry()).
        $ip_address = $this->normalizeEntry($ip_address);
        $query = "INSERT INTO whitelist (ip_address,note) VALUES ( ?, ? )";

        $result = $this->_DB->genQuery($query,array($ip_address, $note));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }

        $sComando = '/usr/bin/issabel-helper fwconfig --add_wl '.escapeshellarg($ip_address).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);

        if ($ret != 0) {
            // No dejar la fila guardada si el firewall no aceptó la entrada:
            // la lista y el set quedarían desincronizados.
            $this->errMsg = trim(implode(' ', $output));
            if ($this->errMsg === '') $this->errMsg = _tr('The IP address could not be added to the firewall');
            $this->_DB->genQuery("DELETE FROM whitelist WHERE ip_address=?", array($ip_address));
            return FALSE;
        }
        return TRUE;

    }

    /**
     * Actualiza una entrada de la whitelist. Si cambia la IP hay que reflejarlo
     * también en el firewall (quitar la vieja y añadir la nueva).
     *
     * La comparación es canónica: `2001:DB8::1` y `2001:db8::1` son la misma
     * dirección y no deben provocar un alta y una baja del mismo elemento.
     */
    function updatewhitelist($old_ip, $ip_address, $note)
    {
        $ip_address = $this->normalizeEntry($ip_address);
        if ($this->normalizeEntry($old_ip) === $ip_address) {
            $query = "UPDATE whitelist SET note=? WHERE ip_address=?";
            $result = $this->_DB->genQuery($query, array($note, $old_ip));
            if ($result == FALSE) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
            return TRUE;
        }

        // Primero se añade la nueva IP: si falla, la entrada anterior sigue
        // intacta y el firewall no queda a medias.
        if ($this->savewhitelist($ip_address, $note) === FALSE) {
            return FALSE;
        }
        if ($this->deletewhitelist($old_ip) === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Forma canónica de una entrada: IPv6 comprimido y en minúsculas, prefijo
     * tal cual. La tabla es UNIQUE sólo por texto, así que sin esto
     * `2001:DB8::1` y `2001:db8::1` se guardarían como dos filas que en el set
     * son el mismo elemento (y borrar una dejaría la otra sin efecto).
     */
    function normalizeEntry($value)
    {
        $value = trim((string)$value);
        $parts = explode('/', $value);
        $packed = @inet_pton($parts[0]);
        if ($packed === false) return $value;
        $address = inet_ntop($packed);
        if (strpos($address, ':') !== false) $address = strtolower($address);
        if (count($parts) === 2 && $parts[1] !== '') return $address.'/'.$parts[1];
        return $address;
    }

    /**
     * Validación de una entrada de la whitelist: IPv4 o IPv6, con prefijo
     * opcional. Devuelve '' si es válida o el mensaje de error.
     *
     * El comodín de cada familia (0.0.0.0 y ::) se rechaza: la whitelist se
     * inserta al principio de INPUT, así que aceptar "todo" allana el firewall.
     */
    function validateIpOrMask($variable) {
        $value = trim((string)$variable);
        if ($value === '') return _tr("Invalid Format");

        $parts = explode('/', $value);
        if (count($parts) > 2) return _tr("Invalid Format");

        $address = $parts[0];
        $isV6 = (strpos($address, ':') !== false);
        $flag = $isV6 ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;
        if (!filter_var($address, FILTER_VALIDATE_IP, $flag)) return _tr("Invalid Format");

        $prefix = null;
        if (count($parts) === 2) {
            $max = $isV6 ? 128 : 32;
            if (!preg_match('/^[0-9]{1,3}$/', $parts[1]) || (int)$parts[1] > $max)
                return _tr("Invalid Format");
            $prefix = (int)$parts[1];
        }

        // Sólo se bloquea el comodín que cubre todo; 0.0.0.0/1 o ::/1 son
        // redes válidas aunque nadie debería usarlas aquí.
        if (($address === '0.0.0.0' || $address === '::') && ($prefix === null || $prefix === 0))
            return _tr("Invalid Format");

        return "";
    }

    public function checkTable() {
        if(!$this->tableExists()) {
            $this->createTable();
        }
    }

    private function createTable() {
        $query = "CREATE TABLE whitelist(
            ip_address TEXT NOT NULL UNIQUE, 
            note TEXT
        )";
        return $this->_DB->genExec($query);
    }
    
    private function tableExists() {
        $query = "SELECT * FROM whitelist";
        $result = $this->_DB->genQuery($query);
        if ($result === false) {
            if (preg_match("/No such table/i", $this->_DB->errMsg))
                return false;
            else
                return true;
        }
        else
            return true;
    }


}
