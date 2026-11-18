-- Whitelist con hostname/DDNS. `ip_address` sigue siendo la IP efectiva que
-- consume el ipset; `hostname` es el nombre que se resuelve periodicamente.
ALTER TABLE whitelist ADD COLUMN hostname text;
ALTER TABLE whitelist ADD COLUMN resolved_at text;
