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
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
*/

/**
 * Registro de los cambios de firewall en /var/log/issabel/audit.log.
 *
 * Lo comparten sec_rules, sec_geoip_map y sec_whitelist (las tres páginas que
 * modifican reglas efectivas del firewall). La línea que se escribe sigue el
 * formato que espera el visor "Security -> Audit":
 *
 *   FIREWALL <usuario>: <evento> <clave=valor ...> result=<ok|error> from <ip>.
 *
 * Ejemplos:
 *
 *   FIREWALL admin: rule.create id=42 traffic=INPUT protocol=TCP target=DROP
 *     src=192.0.2.0/24 dst=0.0.0.0/0 sport=ANY dport=ssh result=ok from 192.0.2.10.
 *   FIREWALL admin: rules.apply mode=first result=error msg="..." from 192.0.2.10.
 *
 * Todos los valores se sanean antes de escribirse: se eliminan los caracteres
 * de control (incluidos CR y LF) y los no imprimibles, y se recortan. Esto no
 * es cosmético: `audit.log` lo lee el jail `issabel-gui` de fail2ban, así que
 * una regla con un salto de línea en un campo libre podría inyectar una línea
 * "LOGIN ... Failed password ..." y provocar el baneo de una IP arbitraria.
 */
class issabelFwAudit
{
    /** Longitud máxima de un valor normal en la línea. */
    const MAX_VALUE = 64;

    /** Longitud máxima del mensaje de error de un helper privilegiado. */
    const MAX_MSG = 200;

    /** Tope de la línea completa, para no crecer sin control. */
    const MAX_LINE = 400;

    /**
     * Escribe una línea de auditoría para una acción sobre el firewall.
     *
     * @param string $event   Identificador de la acción (p. ej. "rule.create").
     * @param string $result  "ok" o "error".
     * @param array  $details Pares clave => valor que se añaden a la línea.
     *                        La clave "msg" se recorta a MAX_MSG.
     */
    public static function log($event, $result, array $details = array())
    {
        $line = sprintf('FIREWALL %s: %s', self::user(), self::clean($event, 40));

        $fields = array();
        foreach ($details as $key => $value) {
            if ($value === NULL || $value === '') continue;

            $key = preg_replace('/[^a-z_]/', '', strtolower((string)$key));
            if ($key === '') continue;

            $max = ($key === 'msg') ? self::MAX_MSG : self::MAX_VALUE;
            $value = self::clean($value, $max);
            if ($value === '') continue;

            // Los valores con espacios (mensajes de iptables, sobre todo) van
            // entre comillas; clean() ya ha quitado las comillas del valor.
            if (strpos($value, ' ') !== FALSE) $value = '"'.$value.'"';
            $fields[] = $key.'='.$value;
        }
        if (count($fields) > 0) $line .= ' '.implode(' ', $fields);

        $line .= ' result='.(($result === 'error') ? 'error' : 'ok');
        $line .= ' from '.self::remote().'.';

        if (strlen($line) > self::MAX_LINE) {
            $line = substr($line, 0, self::MAX_LINE - 3).'...';
        }

        // writeLOG() lo provee framework; el respaldo permite usar la clase en
        // contextos sin la GUI cargada (CLI, pruebas).
        if (function_exists('writeLOG')) writeLOG('audit.log', $line);
        else error_log($line);
    }

    /** Usuario de la sesión, restringido a caracteres seguros. */
    private static function user()
    {
        $user = isset($_SESSION['issabel_user']) ? (string)$_SESSION['issabel_user'] : '';
        $user = preg_replace('/[^A-Za-z0-9._@-]/', '', $user);
        if ($user === '') return 'unknown';
        return substr($user, 0, 32);
    }

    /** IP de origen, validada. */
    private static function remote()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return 'unknown';
        return $ip;
    }

    /**
     * Deja un valor en una sola línea, ASCII imprimible y acotado.
     */
    private static function clean($value, $max)
    {
        $value = (string)$value;
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
        $value = str_replace('"', "'", $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if (strlen($value) > $max) $value = substr($value, 0, $max).'...';
        return $value;
    }
}
?>
