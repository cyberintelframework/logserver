-- SURFids 3.00
-- Database conversion 2.00.02 -> 2.00.03
-- Changeset 008
-- 01-06-2007

-- Changelog
-- 008 Fixed a bug with netconfdetail
-- 007 Added privileges for nepenthes user on stats_dialogue and uniq_binaries
-- 006 Added default value for sensors.status
-- 005 Fixed binaries conversion, updated antivirus stuff
-- 004 Fixed transition from netconf to netconfdetail
-- 003 Added column subject to table report_content
-- 002 Added f-prot updater
-- 001 Initial release

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
INSERT INTO scanners VALUES (2, 'Antivir', 'antivir -rs !bindir!/!file! | grep !file! | awk ''{print $2}'' | awk -F [ ''{print $2}'' | awk -F ] ''{print $1}''', 'antivir --update', 1);
INSERT INTO scanners VALUES (3, 'BitDefender', 'bdc --files !bindir!/!file! | grep !file! | awk ''{print $3}''', 'bdc --update', 0);
INSERT INTO scanners VALUES (4, 'AVAST', '/opt/avast4workstation-1.0.8/bin/avast !bindir!/!file! | grep !file! | grep -v "\\[OK\\]" | tail -n1 | awk -F "infected by:" ''{print $2}'' | awk ''{print $1}'' | awk -F "]" ''{print $1}''', '/home/avast4workstation-1.0.7/bin/avast-update', 0);
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
SELECT DISTINCT text::character varying AS name INTO TABLE uniq_binaries FROM details WHERE type = 8;
ALTER TABLE uniq_binaries ADD COLUMN id serial NOT NULL;

ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT uniq_binaries_pkey PRIMARY KEY (id);
ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT unique_bin UNIQUE (name);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE uniq_binaries TO idslog;
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO idslog;
GRANT INSERT,SELECT ON TABLE uniq_binaries TO nepenthes;
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO nepenthes;

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
UPDATE sensors SET netconfdetail = sensors.netconf WHERE NOT netconf IN ('vlans', 'vland', 'dhcp', 'static');
UPDATE sensors SET netconf = 'static' WHERE NOT netconf IN ('vlans', 'vland', 'dhcp', 'static');
ALTER TABLE sensors ADD COLUMN vlanid integer DEFAULT 0;
ALTER TABLE sensors ALTER COLUMN status SET DEFAULT 0;

--
-- SESSIONS
--
ALTER TABLE sessions ADD COLUMN useragent character varying;

--
-- LOGIN
--
ALTER TABLE login ADD COLUMN gpg integer DEFAULT 0;
ALTER TABLE login DROP COLUMN alltreshold;
ALTER TABLE login DROP COLUMN owntreshold;
ALTER TABLE login DROP COLUMN timeunit;

--
-- REPORT_CONTENT
--
ALTER TABLE report_content ADD COLUMN user_id integer;
ALTER TABLE report_content ADD COLUMN subject character varying;

--
-- STATS_DIALOGUE
--
GRANT INSERT,SELECT ON TABLE stats_dialogue TO nepenthes;
GRANT SELECT,UPDATE ON TABLE stats_dialogue_id_seq TO nepenthes;

