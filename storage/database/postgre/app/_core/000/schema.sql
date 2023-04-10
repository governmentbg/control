CREATE TABLE elections (
  election serial4 NOT NULL,
  name varchar(255) NOT NULL,
  enabled int2 NOT NULL DEFAULT 0,
  slug varchar(50) NOT NULL,
  keyenc varchar(64) NOT NULL,
  CONSTRAINT elections_pk PRIMARY KEY (election)
);
CREATE TABLE miks (
  mik serial4 NOT NULL,
  name varchar(50) NOT NULL,
  CONSTRAINT miks_pk PRIMARY KEY (mik)
);
CREATE TABLE siks (
  sik serial4 NOT NULL,
  election int4 NOT NULL,
  test_key varchar(32) NOT NULL,
  prod_key varchar(32) NOT NULL,
  num varchar(9) NOT NULL,
  mik int4 NOT NULL,
  address varchar(500) NOT NULL,
  video int2 NOT NULL DEFAULT 0,
  CONSTRAINT siks_pk PRIMARY KEY (sik),
  CONSTRAINT siks_fk FOREIGN KEY (election) REFERENCES elections(election) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT siks_fk_1 FOREIGN KEY (mik) REFERENCES miks(mik) ON DELETE RESTRICT ON UPDATE RESTRICT
);
CREATE TABLE modes (
  mode serial4 NOT NULL,
  name varchar(50) NOT NULL,
  enabled int2 NOT NULL DEFAULT 0,
  CONSTRAINT modes_pk PRIMARY KEY (mode)
);
CREATE TABLE devices (
  udi int4 NOT NULL,
  install_key varchar(32) NOT NULL,
  registered timestamp NULL,
  CONSTRAINT devices_pk PRIMARY KEY (udi)
);
CREATE TABLE devices_elections (
  udi int4 NOT NULL,
  election int4 NOT NULL,
  sik int4 NOT NULL,
  registered timestamp NOT NULL,
  CONSTRAINT devices_elections_pk PRIMARY KEY (udi,election,sik),
  CONSTRAINT devices_elections_fk FOREIGN KEY (udi) REFERENCES devices(udi) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT devices_elections_fk_1 FOREIGN KEY (election) REFERENCES elections(election) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT devices_elections_fk_2 FOREIGN KEY (sik) REFERENCES siks(sik) ON DELETE RESTRICT ON UPDATE RESTRICT
);
CREATE TABLE streams (
  stream serial4 NOT NULL,
  udi int4 NOT NULL,
  election int4 NOT NULL,
  sik int4 NOT NULL,
  mode int4 NOT NULL,
  created timestamp NOT NULL,
  started timestamp NULL,
  ended timestamp NULL,
  url varchar(500) NOT NULL,
  CONSTRAINT streams_pk PRIMARY KEY (stream),
  CONSTRAINT streams_fk FOREIGN KEY (udi) REFERENCES devices(udi) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT streams_fk_1 FOREIGN KEY (election) REFERENCES elections(election) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT streams_fk_2 FOREIGN KEY (sik) REFERENCES siks(sik) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT streams_fk_3 FOREIGN KEY (mode) REFERENCES modes(mode) ON DELETE RESTRICT ON UPDATE RESTRICT
);
CREATE TABLE events (
  event serial4 NOT NULL,
  created timestamp NOT NULL,
  type varchar(50) NOT NULL,
  url varchar(500) NOT NULL,
  stream int4 NULL,
  CONSTRAINT events_pk PRIMARY KEY (event),
  CONSTRAINT events_fk FOREIGN KEY (stream) REFERENCES streams(stream) ON DELETE RESTRICT ON UPDATE RESTRICT
);
CREATE TABLE servers (
  server serial4 NOT NULL,
  ip varchar(64) NOT NULL,
  host varchar(255) NOT NULL,
  key varchar(64) NOT NULL,
  enabled int2 NOT NULL DEFAULT 0,
  CONSTRAINT servers_pk PRIMARY KEY (server)
);
ALTER TABLE streams ADD server int4 NOT NULL;
ALTER TABLE streams ADD CONSTRAINT streams_fk_4 FOREIGN KEY (server) REFERENCES servers(server) ON DELETE RESTRICT ON UPDATE RESTRICT;
CREATE TABLE restreamers (
  restreamer serial4 NOT NULL,
  host varchar(255) NOT NULL,
  CONSTRAINT restreamers_pk PRIMARY KEY (restreamer)
);
CREATE TABLE restreamer_servers (
  restreamer int4 NOT NULL,
  server int4 NOT NULL,
  CONSTRAINT restreamer_servers_pk PRIMARY KEY (restreamer,server),
  CONSTRAINT restreamer_servers_fk FOREIGN KEY (restreamer) REFERENCES restreamers(restreamer) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT restreamer_servers_fk_1 FOREIGN KEY (server) REFERENCES servers(server) ON DELETE RESTRICT ON UPDATE RESTRICT
);
CREATE TABLE restreamer_miks (
  restreamer int4 NOT NULL,
  mik int4 NOT NULL,
  CONSTRAINT restreamer_miks_pk PRIMARY KEY (restreamer,mik),
  CONSTRAINT restreamer_miks_fk FOREIGN KEY (restreamer) REFERENCES restreamers(restreamer) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT restreamer_miks_fk_1 FOREIGN KEY (mik) REFERENCES miks(mik) ON DELETE RESTRICT ON UPDATE RESTRICT
);
ALTER TABLE modes ADD enabled_from timestamp NULL;
ALTER TABLE modes ADD enabled_to timestamp NULL;
