ALTER TABLE restreamers ADD ip varchar(64) NULL;
ALTER TABLE restreamers ADD inner_host varchar(255) NOT NULL;
ALTER TABLE servers ADD inner_host varchar(255) NOT NULL;
ALTER TABLE servers ALTER COLUMN ip DROP NOT NULL;
