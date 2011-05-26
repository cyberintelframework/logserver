-- SURFids 3.10
-- Database conversion 3.06+ -> 3.10
--

--
-- Changelog
-- 003 Added Kippo functions
-- 002 Added Kippo tables
-- 001 Initial release
--

--
-- GEOBLOCKS
--
CREATE TABLE geoblocks (
    locid integer NOT NULL,
    ipstart inet,
    ipend inet
);

CREATE INDEX ipend_btree ON geoblocks USING btree (ipend);
CREATE INDEX ipstart_btree ON geoblocks USING btree (ipstart);
GRANT SELECT,INSERT,UPDATE ON TABLE geoblocks TO idslog;

--
-- GEOLOCATIONS
--
CREATE TABLE geolocations (
    locid integer,
    country character varying,
    abbr character varying
);

CREATE INDEX locid_btree ON geolocations USING btree (locid);
GRANT SELECT,INSERT,UPDATE ON TABLE geolocations TO idslog;

--
-- SSH_COMMAND
--
DROP TABLE IF EXISTS ssh_command;
CREATE TABLE ssh_command (
    id integer NOT NULL,
    attackid integer NOT NULL,
    command character varying NOT NULL
);

DROP SEQUENCE IF EXISTS ssh_command_id_seq;
CREATE SEQUENCE ssh_command_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ssh_command_id_seq OWNED BY ssh_command.id;

ALTER TABLE ssh_command ALTER COLUMN id SET DEFAULT nextval('ssh_command_id_seq'::regclass);
ALTER TABLE ONLY ssh_command
    ADD CONSTRAINT ssh_command_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE ssh_command TO idslog;
GRANT SELECT,INSERT ON TABLE ssh_command TO nepenthes;
GRANT SELECT,UPDATE ON SEQUENCE ssh_command_id_seq TO nepenthes;

--
-- SSH_LOGINS
--
DROP TABLE IF EXISTS ssh_logins;
CREATE TABLE ssh_logins (
    id integer NOT NULL,
    attackid integer NOT NULL,
    type boolean DEFAULT false NOT NULL,
    sshuser character varying NOT NULL,
    sshpass character varying NOT NULL
);

DROP SEQUENCE IF EXISTS ssh_logins_id_seq;
CREATE SEQUENCE ssh_logins_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ssh_logins_id_seq OWNED BY ssh_logins.id;

ALTER TABLE ssh_logins ALTER COLUMN id SET DEFAULT nextval('ssh_logins_id_seq'::regclass);
ALTER TABLE ONLY ssh_logins
    ADD CONSTRAINT ssh_logins_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT ON TABLE ssh_logins TO nepenthes;
GRANT SELECT ON TABLE ssh_logins TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ssh_logins_id_seq TO nepenthes;

--
-- SSH_VERSION
--
DROP TABLE IF EXISTS ssh_version;
CREATE TABLE ssh_version (
    id integer NOT NULL,
    attackid integer NOT NULL,
    version integer NOT NULL
);

DROP SEQUENCE IF EXISTS ssh_version_id_seq;
CREATE SEQUENCE ssh_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ssh_version_id_seq OWNED BY ssh_version.id;

ALTER TABLE ssh_version ALTER COLUMN id SET DEFAULT nextval('ssh_version_id_seq'::regclass);
ALTER TABLE ONLY ssh_version
    ADD CONSTRAINT ssh_version_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,UPDATE ON TABLE ssh_version TO nepenthes;
GRANT SELECT ON TABLE ssh_version TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ssh_version_id_seq TO nepenthes;

--
-- UNIQ_SSHVERSION
--
DROP TABLE IF EXISTS uniq_sshversion;
CREATE TABLE uniq_sshversion (
    id integer NOT NULL,
    version character varying
);

DROP SEQUENCE IF EXISTS uniq_sshversion_id_seq;
CREATE SEQUENCE uniq_sshversion_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE uniq_sshversion_id_seq OWNED BY uniq_sshversion.id;
ALTER TABLE uniq_sshversion ALTER COLUMN id SET DEFAULT nextval('uniq_sshversion_id_seq'::regclass);
ALTER TABLE ONLY uniq_sshversion
    ADD CONSTRAINT uniq_sshversion_pkey PRIMARY KEY (id);
ALTER TABLE ONLY uniq_sshversion
    ADD CONSTRAINT uniq_sshversion_version_key UNIQUE (version);

GRANT SELECT ON TABLE uniq_sshversion TO idslog;
GRANT SELECT,INSERT ON TABLE uniq_sshversion TO nepenthes;
GRANT SELECT ON SEQUENCE uniq_sshversion_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE uniq_sshversion_id_seq TO nepenthes;

--
-- BINARIES_DETAIL
--
ALTER TABLE binaries_detail ADD COLUMN first_seen integer DEFAULT 0 NOT NULL;
ALTER TABLE binaries_detail ADD COLUMN last_seen integer DEFAULT 0 NOT NULL;

GRANT SELECT,INSERT,UPDATE ON TABLE binaries_detail TO nepenthes;

--
-- SENSORS
--
ALTER TABLE sensors ALTER COLUMN arp DROP DEFAULT, ALTER COLUMN arp TYPE boolean USING arp::boolean, ALTER COLUMN arp SET DEFAULT false;
ALTER TABLE sensors ADD COLUMN dhcp boolean DEFAULT false NOT NULL;
UPDATE sensors SET dhcp = arp;
ALTER TABLE sensors ADD COLUMN ipv6 boolean DEFAULT false NOT NULL;
UPDATE sensors SET ipv6 = arp;
ALTER TABLE sensors ADD COLUMN protos boolean DEFAULT false NOT NULL;
UPDATE sensors SET protos = arp;

--
-- SENSOR_DETAILS
--
ALTER TABLE sensor_details ADD COLUMN firstattack integer DEFAULT 0 NOT NULL;
ALTER TABLE sensor_details ADD COLUMN updates integer DEFAULT 0 NOT NULL;

--
-- DEACTIVATED_SENSORS
--
ALTER TABLE deactivated_sensors ALTER COLUMN arp DROP DEFAULT, ALTER COLUMN arp TYPE boolean USING arp::boolean, ALTER COLUMN arp SET DEFAULT false;
ALTER TABLE deactivated_sensors ADD COLUMN dhcp boolean DEFAULT false NOT NULL;
UPDATE deactivated_sensors SET dhcp = arp;
ALTER TABLE deactivated_sensors ADD COLUMN ipv6 boolean DEFAULT false NOT NULL;
UPDATE deactivated_sensors SET ipv6 = arp;
ALTER TABLE deactivated_sensors ADD COLUMN protos boolean DEFAULT false NOT NULL;
UPDATE deactivated_sensors SET protos = arp;

--
-- DHCP_STATIC
--
CREATE TABLE dhcp_static (
    id integer NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

CREATE SEQUENCE dhcp_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE dhcp_static_id_seq OWNED BY dhcp_static.id;
ALTER TABLE dhcp_static ALTER COLUMN id SET DEFAULT nextval('dhcp_static_id_seq'::regclass);
ALTER TABLE ONLY dhcp_static ADD CONSTRAINT dhcp_static_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE dhcp_static TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE dhcp_static_id_seq TO idslog;

--
-- IPV6_STATIC
--
CREATE TABLE ipv6_static (
    id integer NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

CREATE SEQUENCE ipv6_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ipv6_static_id_seq OWNED BY ipv6_static.id;

ALTER TABLE ipv6_static ALTER COLUMN id SET DEFAULT nextval('ipv6_static_id_seq'::regclass);
ALTER TABLE ONLY ipv6_static ADD CONSTRAINT ipv6_static_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE ipv6_static TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ipv6_static_id_seq TO idslog;

--
-- surfids3_ipv6_add_by_id
--
DROP FUNCTION IF EXISTS surfids3_ipv6_add_by_id;
CREATE OR REPLACE FUNCTION surfids3_ipv6_add_by_id(integer, inet, integer, integer) RETURNS integer
    AS $_$DECLARE
        p_sensorid      ALIAS FOR $1;
        p_sourceip      ALIAS FOR $2;
        p_severity      ALIAS FOR $3;
        p_atype         ALIAS FOR $4;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks (sensorid, timestamp, source, severity, atype)
        VALUES
                (p_sensorid,
                 extract(epoch from current_timestamp(0))::integer,
                 p_sourceip,
                 p_severity,
                 p_atype
        );

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_arp_add_by_id
--
DROP FUNCTION IF EXISTS surfids3_arp_add_by_id;
CREATE OR REPLACE FUNCTION surfids3_arp_add_by_id(integer, macaddr, macaddr, inet, inet, integer, integer) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_dstmac        ALIAS FOR $2;
        p_srcmac        ALIAS FOR $3;
        p_dstip         ALIAS FOR $4;
        p_srcip         ALIAS FOR $5;
        p_sensorid      ALIAS FOR $6;
        p_atype         ALIAS FOR $7;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks
                (severity,
                 timestamp,
                 src_mac,
                 dst_mac,
                 source,
                 dest,
                 sensorid,
                 atype)
        VALUES
                (p_severity,
                 extract(epoch from current_timestamp(0))::integer,
                 p_srcmac,
                 p_dstmac,
                 p_dstip,
                 p_srcip,
                 p_sensorid,
                 p_atype);

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_sshversion_add
--
DROP FUNCTION IF EXISTS surfids3_sshversion_add;
CREATE OR REPLACE FUNCTION surfids3_sshversion_add(integer, integer) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_versionid ALIAS FOR $2;
BEGIN
    INSERT INTO ssh_version
        (attackid,version)
    VALUES
        (p_attackid,p_versionid);
    return p_versionid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_sshversionstring_add
--
DROP FUNCTION IF EXISTS surfids3_sshversionstring_add;
CREATE OR REPLACE FUNCTION surfids3_sshversionstring_add(integer, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_version ALIAS FOR $2;

    m_versionid INTEGER;
BEGIN
    SELECT INTO m_versionid id FROM uniq_sshversion WHERE version = p_version;

    INSERT INTO ssh_version
        (attackid,version)
    VALUES
        (p_attackid,m_versionid);
    return m_versionid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_sshcommand_add
--
CREATE OR REPLACE FUNCTION surfids3_sshcommand_add(integer, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_command ALIAS FOR $2;

    m_commandid INTEGER;
BEGIN
    INSERT INTO ssh_command
        (attackid,command)
    VALUES
        (p_attackid,p_command);

    SELECT INTO m_commandid currval('ssh_command_id_seq');
    return m_commandid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_sshlogin_add
--
CREATE OR REPLACE FUNCTION surfids3_sshlogin_add(integer, boolean, character varying, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_type ALIAS FOR $2;
    p_user ALIAS FOR $3;
    p_pass ALIAS FOR $4;

    m_loginid INTEGER;
BEGIN
    INSERT INTO ssh_logins
        (attackid,type,sshuser,sshpass)
    VALUES
        (p_attackid,p_type,p_user,p_pass);

    SELECT INTO m_loginid currval('ssh_logins_id_seq');
    return m_loginid;
END$_$
    LANGUAGE plpgsql;

--
-- VERSION
--
UPDATE version SET version = '31000';
