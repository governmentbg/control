CREATE TABLE api_log (
    id serial8 NOT NULL,
    created timestamp NOT NULL,
    type varchar(50) NOT NULL,
    udi int4 NULL,
    sik int4 NULL,
    mode int4 NULL,
    request text NULL,
    response text NULL,
    err int2 NOT NULL DEFAULT 0,
    CONSTRAINT api_log_pk PRIMARY KEY (id),
    CONSTRAINT api_log_devices_udi_fk FOREIGN KEY (udi) REFERENCES devices(udi) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT api_log_siks_sik_fk FOREIGN KEY (sik) REFERENCES siks(sik) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT api_log_modes_mode_fk FOREIGN KEY (mode) REFERENCES modes(mode) ON DELETE RESTRICT ON UPDATE RESTRICT
);
ALTER TABLE api_log ADD ip varchar(64) NULL;
