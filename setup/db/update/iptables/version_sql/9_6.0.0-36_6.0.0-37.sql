-- Tabla `jails` en setup/db (B14) y una fila por jail real, no por grupo.
--
-- `findtime` se declara en el CREATE para una base nueva. En una base que ya
-- tenia la tabla creada en runtime, la columna la agrega
-- IssabelF2BService::creaTablaSiNoExiste() al abrir la pagina; aqui no se
-- puede hacer un ALTER condicional en SQL puro.
CREATE TABLE IF NOT EXISTS jails (
    id        integer primary key,
    name      varchar(30) not null unique,
    maxretry  integer,
    bantime   integer,
    findtime  integer,
    ignoreip  text,
    enabled   integer
);

CREATE UNIQUE INDEX IF NOT EXISTS jails_name_idx ON jails(name);

INSERT OR IGNORE INTO jails(name,maxretry,bantime,ignoreip,enabled) VALUES
 ('asterisk',5,12,'127.0.0.1',1),
 ('asterisk-ami',5,12,'127.0.0.1',1),
 ('apache-auth',5,12,'127.0.0.1',1),
 ('apache-badbots',5,12,'127.0.0.1',1),
 ('apache-botsearch',5,12,'127.0.0.1',1),
 ('apache-fakegooglebot',5,12,'127.0.0.1',1),
 ('apache-modsecurity',5,12,'127.0.0.1',1),
 ('apache-nohome',5,12,'127.0.0.1',1),
 ('apache-noscript',5,12,'127.0.0.1',1),
 ('apache-overflows',5,12,'127.0.0.1',1),
 ('apache-shellshock',5,12,'127.0.0.1',1),
 ('issabel-gui',5,12,'127.0.0.1',1),
 ('sshd',5,12,'127.0.0.1',1),
 ('postfix',5,12,'127.0.0.1',1),
 ('postfix-sasl',5,12,'127.0.0.1',1);

-- El grupo `apache` no es un jail real: sus filas ya estan expandidas.
DELETE FROM jails WHERE name='apache';
