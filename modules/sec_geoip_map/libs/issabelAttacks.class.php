<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
  $Id: issabelAttacks.class.php, Fri 26 Mar 2021 07:03:12 PM EDT, nicolas@issabel.com
*/

class issabelAttacks {

    var $_DB;
    var $errMsg;

    public function __construct(&$pDB) {
        
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB = & $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string) $pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {

            }
        }
        // 1er. Verifico si la tabla register.db existe
        if (!$this->tableExists()) {
            if (!$this->createTable()) {
                $this->errMsg = "The table attacks does not exist and could not be created";
                return false;
            }
        }

    }

    /**
     * Cuenta los ataques por país en la ventana indicada, sin marcarlos como
     * consumidos: el mapa los usa como serie histórica, no como cola.
     *
     * @param int $minutes ventana en minutos (por defecto 24 h)
     * @return array  ISO => total
     */
    public function getAttackCounts($minutes = 1440)
    {
        $minutes = (int)$minutes;
        if ($minutes < 1) $minutes = 1440;
        $query = 'SELECT source, COUNT(*) AS total FROM attacks '
               . 'WHERE datetime>=datetime("now","localtime","-' . $minutes . ' minutes") '
               . 'AND source<>"IP Address not found" GROUP BY source ORDER BY total DESC LIMIT 50';
        $result = $this->_DB->fetchTable($query, true);
        if ($result === false) {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        $out = array();
        foreach ($result as $row) {
            $iso = strtoupper(trim($row['source']));
            if (preg_match('/^[A-Z]{2}$/', $iso)) $out[$iso] = (int)$row['total'];
        }
        return $out;
    }

    public function getAttacks() {
        $query = 'SELECT rowid,source,time(datetime) as hour FROM attacks WHERE datetime>=datetime("now","localtime","-10 minutes") and done=0 and source<>"IP Address not found" LIMIT 2';
        $result = $this->_DB->fetchTable($query, true, array());

        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        foreach($result as $res) {
            $id = $res['rowid'];
            $this->_DB->genQuery("UPDATE attacks SET done=1 WHERE rowid='$id'");
        }
        if(!is_array($result)) {
            return array();
        } else {
            return $result;
        }
    }

    private function createTable() {
        $query = "CREATE TABLE attacks (source text not null, datetime datetime, done int default 0, ip text)";
        return $this->_DB->genExec($query);
    }
    
    private function tableExists() {
        $query = "SELECT * FROM attacks";
        $result = $this->_DB->genQuery($query);
        if ($result === false) {
            if (preg_match("/No such table/i", $this->_DB->errMsg)) {
                return false;
            }
            else {
                return true;
            }
        }
        else {
            return true;
        }
    }
    
}
?>
