CREATE TABLE recordings (
    recording SERIAL NOT NULL,
    created timestamp NOT NULL,
    src varchar(50) NOT NULL,
    udi int4 NULL,
    sik int4 NULL,
    mode int4 NULL,
    url text NULL,
    CONSTRAINT recordings_pk PRIMARY KEY (recording),
    CONSTRAINT recordings_devices_udi_fk FOREIGN KEY (udi) REFERENCES devices(udi) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT recordings_siks_sik_fk FOREIGN KEY (sik) REFERENCES siks(sik) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT recordings_modes_mode_fk FOREIGN KEY (mode) REFERENCES modes(mode) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE INDEX streams_mode_ended_idx ON streams (ended, mode);
CREATE INDEX servers_enabled ON servers (enabled);
CREATE INDEX streams_sme_idx ON streams (server, mode, election, sik);
