-- IPv6 en el firewall: la columna `family` separa IPv4 (`inet`) de IPv6
-- (`inet6`). Las reglas que ya existen quedan en `inet`, asi que un upgrade no
-- cambia ninguna regla IPv4 ni enciende el filtrado IPv6.
--
-- `ipv6_enabled` es el opt-in: en 0, fwconfig no toca ip6tables. Se enciende
-- desde la pagina de reglas (switch "IPv6") y recien se aplica con "Aplicar".
ALTER TABLE filter ADD COLUMN family TEXT NOT NULL DEFAULT 'inet';
ALTER TABLE tmp_execute ADD COLUMN ipv6_enabled INTEGER NOT NULL DEFAULT 0;

-- El snapshot de "Descartar cambios" se recrea con SELECT * FROM filter en
-- cada aplicacion. Si sobrevive al upgrade tiene menos columnas que `filter` y
-- el INSERT ... SELECT * de discardChanges() falla con "table filter has N
-- columns but M values were supplied".
DROP TABLE IF EXISTS filter_snapshot;

-- Semillas IPv6: loopback, ICMPv6 (NDP/SLAAC dependen de el) y estado
-- establecido/relacionado. Son las mismas tres guardas que los seeds IPv4,
-- visibles y editables en la grilla. El default-DENY de IPv6 (REJECT) no se
-- siembra: lo emite fwconfig al final de la cadena, para que una regla nueva
-- quede siempre por encima y nadie pueda dejar IPv6 en fail-open borrandolo.
--
-- Cada INSERT es idempotente por su propia firma, porque en una instalacion
-- limpia issabel-dbprocess aplica el esquema y despues todos los version_sql.
INSERT INTO filter (traffic, eth_in, eth_out, ip_source, ip_destiny, protocol,
                    sport, dport, icmp_type, number_ip, target, rule_order,
                    activated, state, family)
SELECT 'INPUT', 'lo', '', '::/0', '::/0', 'ALL', '', '', '', '', 'ACCEPT',
       (SELECT COALESCE(MAX(rule_order), 0) + 1 FROM filter WHERE protocol <> 'GEOIP'),
       1, NULL, 'inet6'
WHERE NOT EXISTS (SELECT 1 FROM filter
                  WHERE family = 'inet6' AND protocol = 'ALL' AND traffic = 'INPUT');

INSERT INTO filter (traffic, eth_in, eth_out, ip_source, ip_destiny, protocol,
                    sport, dport, icmp_type, number_ip, target, rule_order,
                    activated, state, family)
SELECT 'INPUT', 'ANY', '', '::/0', '::/0', 'ICMP', '', '', 'ANY', '', 'ACCEPT',
       (SELECT COALESCE(MAX(rule_order), 0) + 1 FROM filter WHERE protocol <> 'GEOIP'),
       1, NULL, 'inet6'
WHERE NOT EXISTS (SELECT 1 FROM filter
                  WHERE family = 'inet6' AND protocol = 'ICMP' AND traffic = 'INPUT');

INSERT INTO filter (traffic, eth_in, eth_out, ip_source, ip_destiny, protocol,
                    sport, dport, icmp_type, number_ip, target, rule_order,
                    activated, state, family)
SELECT 'INPUT', 'ANY', '', '::/0', '::/0', 'STATE', '', '', '', '', 'ACCEPT',
       (SELECT COALESCE(MAX(rule_order), 0) + 1 FROM filter WHERE protocol <> 'GEOIP'),
       1, 'Established,Related', 'inet6'
WHERE NOT EXISTS (SELECT 1 FROM filter
                  WHERE family = 'inet6' AND protocol = 'STATE' AND traffic = 'INPUT');
