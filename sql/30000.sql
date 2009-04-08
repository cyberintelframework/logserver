-- SURFids 2.10
-- Database conversion 2.03 -> 2.10
-- Changeset 008
-- 13-11-2008
--

--
-- Changelog
-- 008 Added scanner modifications
-- 007 Added missing binaries_detail.bin conversion
-- 006 Added type conversion of severity.val
-- 005 Added pageconf, report_content, indexmods
-- 004 Removed report_content modification
-- 003 Added report_content modification
-- 002 Added LOGIN modifications
-- 001 Initial release
--

--
-- VERSION
--

CREATE TABLE version (
    version integer NOT NULL
);
INSERT INTO version VALUES (30000);

--
-- SENSORS
--
ALTER TABLE sensors ADD COLUMN networkconfig character varying;
UPDATE sensors SET networkconfig = netconfdetail;

ALTER TABLE sensors DROP COLUMN remoteip;
ALTER TABLE sensors DROP COLUMN localip;
ALTER TABLE sensors DROP COLUMN lastupdate;
ALTER TABLE sensors DROP COLUMN action;
ALTER TABLE sensors DROP COLUMN ssh;
ALTER TABLE sensors DROP COLUMN netconf;
ALTER TABLE sensors DROP COLUMN server;
ALTER TABLE sensors DROP COLUMN netconfdetail;
ALTER TABLE sensors DROP COLUMN rev;
ALTER TABLE sensors DROP COLUMN version;
ALTER TABLE sensors DROP COLUMN sensormac;

--
-- SENSOR_DETAILS
--
CREATE TABLE sensor_details (
    id integer NOT NULL,
    keyname character varying NOT NULL,
    remoteip inet,
    localip inet,
    sensormac macaddr,
    mainif character varying,
    trunkif character varying,
    ssh integer DEFAULT 0 NOT NULL,
    action character varying,
    lastupdate integer DEFAULT 0 NOT NULL,
    rev integer DEFAULT 0 NOT NULL,
    sensortype character varying,
    mainconf character varying,
    osversion character varying,
    dns1 inet,
    dns2 inet,
    permanent integer DEFAULT 0 NOT NULL
);

CREATE SEQUENCE sensor_details_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sensor_details_id_seq OWNED BY sensor_details.id;
ALTER TABLE sensor_details ALTER COLUMN id SET DEFAULT nextval('sensor_details_id_seq'::regclass);
ALTER TABLE ONLY sensor_details
    ADD CONSTRAINT sensor_details_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,UPDATE ON TABLE sensor_details TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE sensor_details_id_seq TO idslog;

--
-- SENSORS_LOG
--
DROP TABLE IF EXISTS sensors_log;

--
-- SERVERS
--
DROP TABLE IF EXISTS servers;

--
-- STATS_HISTORY
--
DROP TABLE IF EXISTS stats_history;

--
-- STATS_HISTORY_DIALOGUE
--
DROP TABLE IF EXISTS stats_history_dialogue;

--
-- STATS_HISTORY_VIRUS
--
DROP TABLE IF EXISTS stats_history_virus;

--
-- ARP_EXCL
--
CREATE TABLE arp_excl (
    id serial NOT NULL,
    mac macaddr NOT NULL
);

ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_mac_key UNIQUE (mac);

ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE arp_excl TO idslog;

--
-- INDEXMODS
--
CREATE TABLE indexmods (
    id serial NOT NULL,
    phppage character varying
);

SELECT pg_catalog.setval(pg_catalog.pg_get_serial_sequence('indexmods', 'id'), 11, true);

INSERT INTO indexmods VALUES (1, 'mod_attacks.php');
INSERT INTO indexmods VALUES (2, 'mod_exploits.php');
INSERT INTO indexmods VALUES (3, 'mod_search.php');
INSERT INTO indexmods VALUES (4, 'mod_top10attackers.php');
INSERT INTO indexmods VALUES (5, 'mod_top10protocols.php');
INSERT INTO indexmods VALUES (6, 'mod_virusscanners.php');
INSERT INTO indexmods VALUES (7, 'mod_crossdom.php');
INSERT INTO indexmods VALUES (8, 'mod_maloffered.php');
INSERT INTO indexmods VALUES (9, 'mod_sensorstatus.php');
INSERT INTO indexmods VALUES (10, 'mod_top10ports.php');
INSERT INTO indexmods VALUES (11, 'mod_top10sensors.php');

ALTER TABLE ONLY indexmods
    ADD CONSTRAINT indexmods_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE indexmods TO idslog;

--
-- INDEXMODS_SELECTED
--
CREATE TABLE indexmods_selected (
    login_id integer,
    indexmod_id integer
);

ALTER TABLE ONLY indexmods_selected
    ADD CONSTRAINT foreign_indexmods FOREIGN KEY (login_id) REFERENCES "login"(id) ON DELETE CASCADE;

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE indexmods_selected TO idslog;

--
-- LOGIN
--
ALTER TABLE login ADD COLUMN d_plotter integer DEFAULT 0 NOT NULL;
ALTER TABLE login ADD COLUMN d_plottype integer DEFAULT 1 NOT NULL;
ALTER TABLE login ADD COLUMN d_utc integer DEFAULT 0 NOT NULL;

--
-- PAGECONF
--
CREATE TABLE pageconf (
    userid integer DEFAULT 0 NOT NULL,
    pageid integer DEFAULT 0 NOT NULL,
    config character varying
);

ALTER TABLE ONLY pageconf
    ADD CONSTRAINT unique_pageconf UNIQUE (userid, pageid);

ALTER TABLE ONLY pageconf
    ADD CONSTRAINT foreign_pageconf FOREIGN KEY (userid) REFERENCES "login"(id) ON DELETE CASCADE;

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE pageconf TO idslog;

--
-- REPORT_CONTENT
--
ALTER TABLE report_content ADD COLUMN always integer DEFAULT 0 NOT NULL;
ALTER TABLE report_content ADD COLUMN utc integer DEFAULT 0 NOT NULL;

ALTER TABLE report_content
    DROP CONSTRAINT foreign_report_content_login_id;

ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES "login"(id) ON DELETE CASCADE;

--
-- SCANNERS
--
TRUNCATE TABLE scanners;
ALTER TABLE scanners DROP COLUMN command;
ALTER TABLE scanners DROP COLUMN update;
ALTER TABLE scanners DROP COLUMN vercommand;
ALTER TABLE scanners ADD COLUMN getvirus character varying;
ALTER TABLE scanners ADD COLUMN matchvirus character varying;
ALTER TABLE scanners ADD COLUMN getbin character varying;
ALTER TABLE scanners ADD COLUMN matchclean character varying;

SELECT pg_catalog.setval('scanners_id_seq', 6, true);

INSERT INTO scanners VALUES (2, 'Antivir', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (3, 'BitDefender', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (6, 'Kaspersky', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (4, 'AVAST', 0, '', E'.*\\[infected by: *(.*) *\\[.*\\]\\]$', E'.*\\[infected by:.*', E'.*\\/([0-9A-Za-z]*).*\\[.*\\]$', E'.*\\[OK\\]$');
INSERT INTO scanners VALUES (5, 'F-Prot', 0, '', E'.*\\[Found .*\\].*<(.*)> {1,}.*', E'.*\\[Found .*\\].*', E'.*\\[.*\\] {1,}.*([a-zA-Z0-9]{32}).*', E'.*\\[Clean\\].*');
INSERT INTO scanners VALUES (1, 'ClamAV', 0, '', NULL, NULL, NULL, NULL);

--
-- SEVERITY
--
ALTER TABLE severity ALTER val TYPE integer USING val::integer;

--
-- SENSOR_NOTES
--
CREATE TABLE sensor_notes (
    id integer NOT NULL,
    keyname character varying NOT NULL,
    ts integer DEFAULT date_part('epoch'::text, now()) NOT NULL,
    note text,
    vlanid integer,
    admin integer DEFAULT 0 NOT NULL,
    type integer DEFAULT 1 NOT NULL
);

CREATE SEQUENCE sensor_notes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sensor_notes_id_seq OWNED BY sensor_notes.id;
ALTER TABLE sensor_notes ALTER COLUMN id SET DEFAULT nextval('sensor_notes_id_seq'::regclass);
ALTER TABLE ONLY sensor_notes
    ADD CONSTRAINT sensor_notes_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sensor_notes TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE sensor_notes_id_seq TO idslog;

--
-- SYSLOG
--
CREATE TABLE syslog (
    id integer NOT NULL,
    source character varying NOT NULL,
    error character varying NOT NULL,
    args character varying,
    level integer DEFAULT 0 NOT NULL,
    keyname character varying,
    device character varying,
    pid integer DEFAULT 0 NOT NULL,
    vlanid integer DEFAULT 0 NOT NULL,
    "timestamp" timestamp without time zone DEFAULT ('now'::text)::timestamp(4) without time zone NOT NULL
);

CREATE SEQUENCE syslog_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE syslog_id_seq OWNED BY syslog.id;
ALTER TABLE syslog ALTER COLUMN id SET DEFAULT nextval('syslog_id_seq'::regclass);
ALTER TABLE ONLY syslog
    ADD CONSTRAINT syslog_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE syslog TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE syslog_id_seq TO idslog;

--
-- DEACTIVATED_ATTACKS
--
CREATE TABLE deactivated_attacks (
    id integer NOT NULL,
    "timestamp" integer NOT NULL,
    severity integer NOT NULL,
    source inet NOT NULL,
    sport integer DEFAULT 0 NOT NULL,
    dest inet,
    dport integer DEFAULT 0 NOT NULL,
    sensorid integer NOT NULL,
    src_mac macaddr,
    dst_mac macaddr,
    atype integer DEFAULT 0 NOT NULL
);

CREATE SEQUENCE deactivated_attacks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE deactivated_attacks_id_seq OWNED BY deactivated_attacks.id;
ALTER TABLE deactivated_attacks ALTER COLUMN id SET DEFAULT nextval('deactivated_attacks_id_seq'::regclass);

GRANT SELECT,INSERT,DELETE ON TABLE deactivated_attacks TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE deactivated_attacks_id_seq TO idslog;

--
-- DEACTIVATED_DETAILS
--
CREATE TABLE deactivated_details (
    id integer NOT NULL,
    attackid integer NOT NULL,
    sensorid integer NOT NULL,
    type integer NOT NULL,
    text text NOT NULL
);

CREATE SEQUENCE deactivated_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE deactivated_details_id_seq OWNED BY deactivated_details.id;
ALTER TABLE deactivated_details ALTER COLUMN id SET DEFAULT nextval('deactivated_details_id_seq'::regclass);

GRANT SELECT,INSERT,DELETE ON TABLE deactivated_details TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE deactivated_details_id_seq TO idslog;

--
-- DEACTIVATED_SENSORS
--
CREATE TABLE deactivated_sensors (
    id integer NOT NULL,
    keyname character varying NOT NULL,
    laststart integer,
    status integer DEFAULT 0,
    uptime integer,
    laststop integer,
    tap character varying,
    tapip inet,
    mac macaddr,
    organisation integer DEFAULT 0 NOT NULL,
    vlanid integer DEFAULT 0,
    arp integer DEFAULT 0 NOT NULL,
    label character varying,
    networkconfig character varying
);

CREATE SEQUENCE deactivated_sensors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE deactivated_sensors_id_seq OWNED BY deactivated_sensors.id;
ALTER TABLE deactivated_sensors ALTER COLUMN id SET DEFAULT nextval('deactivated_sensors_id_seq'::regclass);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE deactivated_sensors TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE deactivated_sensors_id_seq TO idslog;

--
-- GROUPMEMBERS
--
CREATE TABLE groupmembers (
    id integer NOT NULL,
    sensorid integer NOT NULL,
    groupid integer NOT NULL
);

CREATE SEQUENCE groupmembers_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE groupmembers_id_seq OWNED BY groupmembers.id;
ALTER TABLE groupmembers ALTER COLUMN id SET DEFAULT nextval('groupmembers_id_seq'::regclass);
ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE groupmembers TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE groupmembers_id_seq TO idslog;

--
-- GROUPS
--
CREATE TABLE groups (
    id integer NOT NULL,
    name character varying NOT NULL,
    owner integer NOT NULL
);

CREATE SEQUENCE groups_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE groups_id_seq OWNED BY groups.id;
ALTER TABLE groups ALTER COLUMN id SET DEFAULT nextval('groups_id_seq'::regclass);
ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE groups TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE groups_id_seq TO idslog;

--
-- FUNCTION first_attack(sensorid)
--
CREATE FUNCTION first_attack(integer) RETURNS timestamp without time zone
    AS $_$DECLARE
    i_sid ALIAS FOR $1;
    i_ts INTEGER;
    i_timestamp TIMESTAMP;
BEGIN
    SELECT INTO i_ts timestamp FROM attacks WHERE sensorid = i_sid ORDER BY timestamp ASC LIMIT 1;
    SELECT INTO i_timestamp TIMESTAMP 'epoch' + i_ts * INTERVAL '1 second';
    RETURN date_trunc('day', i_timestamp);
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION epoch_to_ts(epoch timestamp)
--
CREATE FUNCTION epoch_to_ts(integer) RETURNS timestamp without time zone
    AS $_$DECLARE
    i_epoch ALIAS FOR $1;
BEGIN
    RETURN TIMESTAMP WITH TIME ZONE 'epoch' + i_epoch * INTERVAL '1 second';
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION ts_to_epoch(timestamp)
--
CREATE FUNCTION ts_to_epoch(timestamp without time zone) RETURNS integer
    AS $_$DECLARE
    i_ts ALIAS FOR $1;
BEGIN
    RETURN date_part('epoch', i_ts)::integer;
END$_$
    LANGUAGE plpgsql;

