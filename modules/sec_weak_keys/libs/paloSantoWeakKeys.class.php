<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-31                                             |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
  $Id: paloSantoWeakKeys.class.php, Thu 20 May 2021 09:00:36 AM EDT, nicolas@issabel.com
*/
class paloSantoWeakKeys {
    var $_DB;      // Reference to the active DB
    var $errMsg;   // Variable where the errors are stored


    /**
     * Constructor of the class, receives as a parameter the database, which is stored in the class variable $_DB
     *  .
     * @param string    $pDB     object of the class paloDB    
     */
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

    /**
     * Function that returns the number of devices sip or iax in the database
     *  .
     * @param string    $filter_field       string that indicates the filter applying 
     * @param string    $filter_value       string that has the value of the filter
     *
     * @return integer  0 in case of an error or the number of devices sip, iax or pjsip in the database
     */
    function getNumWeakKeys($filter_field,$filter_value)
    {
        $where = "";
        $arrParam = array();
        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
           $arrParam = array("$filter_value%", "$filter_value%");
           $where = " AND d.id LIKE ?";
        }

        // Solo se cuentan los dispositivos de una tecnologia soportada que
        // tienen fila de secreto, para que el total coincida con lo que
        // devuelve getWeakKeys() (SIP, IAX2 y PJSIP).
        $query = "SELECT COUNT(*) FROM (".
                    "SELECT d.id FROM devices d INNER JOIN sip s ON s.id=d.id AND s.keyword='secret' ".
                        "WHERE d.tech IN ('sip','pjsip')$where".
                    " UNION ALL ".
                    "SELECT d.id FROM devices d INNER JOIN iax i ON i.id=d.id AND i.keyword='secret' ".
                        "WHERE d.tech='iax2'$where".
                 ") t";

        $result=$this->_DB->getFirstRowQuery($query,false,$arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    /**
     * Function that returns an array with all the devices sip, iax or pjsip in the database with their respective key 
     *
     * @param integer    $limit             Value to limit the result of the query
     * @param integer    $offset            Value for the offset of the query
     * @param string     $filter_field      string that indicates the filter applying 
     * @param string     $filter_value      string that has the value of the filter
     *
     * @return array   empty if an error occurs or the data with the key of the devices
     */
    function getWeakKeys($limit,$offset,$filter_field,$filter_value)
    {
        $where = "";
        $arrParam = array($limit,$offset);
        if(isset($filter_field) && $filter_field !="" && $filter_value != ""){
            $where = " AND d.id LIKE ?";
            $arrParam = array("$filter_value%","$filter_value%",$limit,$offset);
        }

        // SIP y PJSIP comparten la tabla sip (en PJSIP el id sigue siendo la
        // extension); IAX2 usa la tabla iax. La union se pagina como un solo
        // conjunto para no perder filas entre tecnologias.
        $query = "SELECT d.id AS id,d.description AS description,s.data AS data,d.tech AS tech ".
                    "FROM devices d INNER JOIN sip s ON s.id=d.id AND s.keyword='secret' ".
                    "WHERE d.tech IN ('sip','pjsip')$where".
                 " UNION ALL ".
                 "SELECT d.id AS id,d.description AS description,i.data AS data,d.tech AS tech ".
                    "FROM devices d INNER JOIN iax i ON i.id=d.id AND i.keyword='secret' ".
                    "WHERE d.tech='iax2'$where".
                 " ORDER BY id LIMIT ? OFFSET ?";

        $result=$this->_DB->fetchTable($query, true, $arrParam);
        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    /**
     * Function that searches in the database an existing device with its respective key
     *
     * @param string     $id                id of the device to be searched
     *
     * @return mixed     null if an error occurs or an array with all the data of the device
     */
    function getWeakKeyById($id)
    {
        $arrParam = array($id);
        $query = "SELECT id,tech FROM devices WHERE id=?";
    
        $result=$this->_DB->getFirstRowQuery($query,true,$arrParam);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        $device = $result;
        if($device['tech'] == "sip" || $device['tech'] == "pjsip"){
            $arrParam = array($id);
            $query = "select devices.id,description,tech,data from devices,sip where devices.id=sip.id and keyword = 'secret' and devices.id = ?";
            $result=$this->_DB->fetchTable($query, true, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return array();
            }
            return $result[0];
        }
        if($device['tech'] == "iax2"){
            $arrParam = array($id);
            $query = "select devices.id,description,tech,data from devices,iax where devices.id=iax.id and keyword = 'secret' and devices.id = ?";
            $result= $this->_DB->fetchTable($query, true, $arrParam);
            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return array();
            }
            return $result[0];
        }
        return array();
    }

    /**
     * Function that updates the key of an existing device in the database 
     *
     * @param array     $arrValues        array that contains the new key and the id of the device
     * @param string    $tech             string that contains the technology of the device (sip, pjsip or iax2)
     *
     * @return bool     false if an error occurs or true if the key is correctly updated
     */
    function saveNewKey($arrValues, $tech)
    {
        $arrParam = array($arrValues['new_key'],$arrValues['id']);
        // paloDB enlaza las cadenas numericas como enteros, y una comparacion
        // entera sobre el id (varchar) obliga a MariaDB a convertir filas como
        // 'pjsip-1' o 'tr-peer-1', lo que falla en modo estricto. El cast deja
        // la comparacion en texto.
        if($tech == "sip" || $tech == "pjsip"){
            $query = "update sip set data = ? where id = cast(? as char) and keyword = 'secret'";
            $result = $this->_DB->genQuery($query,$arrParam);
            if( $result == false ){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }    
            return true;
        }
        if($tech == "iax2"){
            $query = "update iax set data = ? where id = cast(? as char) and keyword = 'secret'";
            $result = $this->_DB->genQuery($query,$arrParam);
            if( $result == false ){
                $this->errMsg = $this->_DB->errMsg;
                return false;
            }    
            return true;
        }
        // Tecnologia no soportada: no hay nada que actualizar.
        return false;
    }
}
?>
