-- Block List (espejo de la whitelist) y prioridad de las reglas.
--
-- `priority` no se declara en el esquema de instalación: una instalación
-- limpia aplica 1_schema.sql y después todos los version_sql, así que el
-- ALTER de aquí es el único camino y no se duplica la columna.
CREATE TABLE IF NOT EXISTS blocklist (
    ip_address TEXT NOT NULL UNIQUE,
    note       TEXT,
    created    TEXT NOT NULL DEFAULT (datetime('now'))
);

ALTER TABLE filter ADD COLUMN priority varchar(10) NOT NULL DEFAULT 'normal';
