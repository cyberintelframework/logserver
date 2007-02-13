-- SURFnet IDS SQL changes for 1.04
-- Version: 1.04.02
-- 13-02-2007

-- Changelog
-- 1.04.02 Added f-prot updater
-- 1.04.01 Initial release

--
-- NORMAN
--
CREATE TABLE norman (
    binid integer NOT NULL,
    result text
);

ALTER TABLE ONLY norman
    ADD CONSTRAINT norman_binid_key UNIQUE (binid);

GRANT INSERT,SELECT,UPDATE ON TABLE norman TO idslog;

--
-- SCANNERS
--
CREATE TABLE scanners (
    id serial NOT NULL,
    name character varying,
    command character varying,
    "update" character varying,
    status integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE scanners TO idslog;
GRANT SELECT,UPDATE ON TABLE scanners_id_seq TO idslog;

INSERT INTO scanners VALUES (1, 'ClamAV', 'clamscan --no-summary !bindir!/!file! | grep !file! | awk ''{print $2}'' | grep -v ^OK$', 'freshclam', 0);
INSERT INTO scanners VALUES (2, 'Antivir', 'antivir -rs !bindir!/!file! | grep !file! | awk ''{print $2}'' | awk -F [ ''{print $2}''', 'antivir --update', 0);
INSERT INTO scanners VALUES (3, 'BitDefender', 'bdc --files !bindir!/!file! | grep !file! | awk ''{print $3}''', 'bdc --update', 0);
INSERT INTO scanners VALUES (4, 'AVAST', '/opt/avast4workstation-1.0.7/bin/avast !bindir!/!file! | grep !file! | grep -v "\\[OK\\]" | tail -n1 | awk -F "infected by:" ''{print $2}'' | awk ''{print $1}'' | awk -F "]" ''{print $1}''', '/home/avast4workstation-1.0.7/bin/avast-update', 0);
INSERT INTO scanners VALUES (5, 'F-Prot', 'f-prot !bindir!/!file! | grep !file! | grep -v Search: | grep -vi error | awk ''{print $NF}''', '/opt/f-prot/tools/check-updates.pl', 0);
INSERT INTO scanners VALUES (6, 'Kaspersky', '/opt/kav/5.5/kav4unix/bin/kavscanner !bindir!/!file! | grep !file! | tail -n1 | awk ''{print $NF}'' | grep -v ^OK$', '/home/kav/5.5/kav4unix/bin/keepup2date', 0);

--
-- SEARCHTEMPLATE
--
CREATE TABLE searchtemplate (
    id serial NOT NULL,
    title character varying NOT NULL,
    querystring character varying NOT NULL,
    userid integer NOT NULL
);

ALTER TABLE ONLY searchtemplate
    ADD CONSTRAINT searchtemplate_primary_id UNIQUE (id);

GRANT INSERT,SELECT,RULE,UPDATE,DELETE,REFERENCES ON TABLE searchtemplate TO idslog;
GRANT SELECT,UPDATE ON TABLE searchtemplate_id_seq TO idslog;

--
-- SERVERSTATS
--
CREATE TABLE serverstats (
    id serial NOT NULL,
    "timestamp" integer NOT NULL,
    "type" character varying NOT NULL,
    label character varying NOT NULL,
    "interval" character varying NOT NULL,
    image character varying NOT NULL,
    server character varying NOT NULL
);

ALTER TABLE ONLY serverstats
    ADD CONSTRAINT primary_serverstats PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE serverstats TO idslog;
GRANT SELECT,UPDATE ON TABLE serverstats_id_seq TO idslog;

--
-- UNIQ_BINARIES
--
SELECT DISTINCT name::character varying INTO TABLE uniq_binaries FROM details WHERE type = 8;
ALTER TABLE uniq_binaries ADD COLUMN id serial NOT NULL;

ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT uniq_binaries_pkey PRIMARY KEY (id);
ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT unique_bin UNIQUE (name);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE uniq_binaries TO idslog;
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO idslog;

--
-- BINARIES
--
UPDATE binaries SET info = stats_virus.id WHERE info = stats_virus.name;
ALTER TABLE binaries ALTER COLUMN info TYPE integer USING binaries.info::integer;

UPDATE binaries SET bin = uniq_binaries.id WHERE bin = uniq_binaries.name;
ALTER TABLE binaries ALTER COLUMN bin TYPE integer USING binaries.bin::integer;

UPDATE binaries SET scanner = scanners.id WHERE scanner = scanners.name;
ALTER TABLE binaries ALTER COLUMN scanner TYPE integer USING binaries.scanner::integer;

--
-- ORG_ID
--
ALTER TABLE org_id ADD COLUMN type integer;

--
-- SENSORS
--
ALTER TABLE sensors ADD COLUMN netconfdetail text;
ALTER TABLE sensors ADD COLUMN vlanid integer DEFAULT 0;

--
-- SESSIONS
--
ALTER TABLE sessions ADD COLUMN useragent character varying;

--
-- LOGIN
--
ALTER TABLE login ADD COLUMN gpg integer DEFAULT 0;

--
-- REPORT_CONTENT
--
ALTER TABLE report_content ADD COLUMN user_id integer;
