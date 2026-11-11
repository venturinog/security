-- Distingue "nunca activado" de "desactivado por el usuario" para el aviso
-- de la página de reglas. `first_time` vale 1 en ambos casos desde que se
-- agregó "Desactivar firewall"; este flag sólo lo pone la desactivación
-- explícita y lo limpia la activación.
ALTER TABLE tmp_execute ADD COLUMN deactivated integer not null default 0;
