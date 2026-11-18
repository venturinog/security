-- Responsive Firewall simple: una sola fila, apagado por defecto.
CREATE TABLE IF NOT EXISTS responsive (
    id            integer primary key check (id = 1),
    enabled       integer not null default 0,
    allow_timeout integer not null default 7200,
    sip_port      integer not null default 5060,
    rtp_start     integer not null default 10000,
    rtp_end       integer not null default 20000,
    probe_rate    integer not null default 6,
    probe_burst   integer not null default 3
);
INSERT OR IGNORE INTO responsive(id,enabled) VALUES (1,0);
