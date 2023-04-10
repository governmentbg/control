ALTER TABLE devices ALTER COLUMN install_key TYPE varchar(14) USING install_key::varchar;
ALTER TABLE siks ALTER COLUMN prod_key TYPE varchar(14) USING prod_key::varchar;
ALTER TABLE siks ALTER COLUMN test_key TYPE varchar(14) USING test_key::varchar;
ALTER TABLE servers RENAME COLUMN "key" TO key_setup;
ALTER TABLE servers ADD key_sik varchar(64) NOT NULL;
ALTER TABLE servers ADD key_real varchar(64) NOT NULL;
ALTER TABLE streams ALTER COLUMN sik DROP NOT NULL;
