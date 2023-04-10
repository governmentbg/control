ALTER TABLE siks ADD insite int2 NOT NULL DEFAULT 1;

CREATE TABLE monitor (
    created timestamp NOT NULL,
    server int4 NULL DEFAULT NULL,
    restreamer int4 NULL DEFAULT NULL,
    data jsonb NULL DEFAULT NULL,
    CONSTRAINT monitor_servers_fk FOREIGN KEY (server) REFERENCES servers(server) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT monitor_restreamers_fk FOREIGN KEY (restreamer) REFERENCES restreamers(restreamer) ON DELETE RESTRICT ON UPDATE RESTRICT
);

ALTER TABLE servers ADD monitor jsonb NULL DEFAULT NULL;
ALTER TABLE restreamers ADD monitor jsonb NULL DEFAULT NULL;
