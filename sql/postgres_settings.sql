--
-- SURFids 2.10
-- Database structure
-- Changeset 006
-- 25-02-2009
--

--
-- Version history
-- 006 Fixed plpgsql and escaping of virus definitions
-- 005 A fresh schema of the development database
-- 004 Modified report_content, added pageconf, indexmods
-- 003 Added ostypes & modified system, login, report_content
-- 002 Added arp_excl
-- 001 Initial release
--

CREATE PROCEDURAL LANGUAGE plpgsql;
ALTER PROCEDURAL LANGUAGE plpgsql OWNER TO postgres;

--
-- VERSION
--

CREATE TABLE version (
    version character varying NOT NULL
);
INSERT INTO version VALUES ('30000');

--
-- SENSORS 
--

CREATE TABLE sensors (
    id integer NOT NULL,
    keyname character varying NOT NULL,
    laststart integer,
    status integer DEFAULT 0,
    uptime integer DEFAULT 0,
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

CREATE SEQUENCE sensors_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sensors_id_seq OWNED BY sensors.id;
ALTER TABLE sensors ALTER COLUMN id SET DEFAULT nextval('sensors_id_seq'::regclass);
ALTER TABLE ONLY sensors
    ADD CONSTRAINT primary_sensors PRIMARY KEY (id);

CREATE UNIQUE INDEX index_sensors_id ON sensors USING btree (id);
ALTER TABLE sensors CLUSTER ON index_sensors_id;
CREATE INDEX index_sensors_organisation ON sensors USING btree (organisation);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sensors TO idslog;
GRANT SELECT ON TABLE sensors TO nepenthes;
GRANT SELECT ON TABLE sensors TO argos;

GRANT SELECT,UPDATE ON SEQUENCE sensors_id_seq TO idslog;
GRANT SELECT ON SEQUENCE sensors_id_seq TO nepenthes;

--
-- ATTACKS 
--

CREATE TABLE attacks (
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

CREATE SEQUENCE attacks_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE attacks_id_seq OWNED BY attacks.id;
ALTER TABLE attacks ALTER COLUMN id SET DEFAULT nextval('attacks_id_seq'::regclass);
ALTER TABLE ONLY attacks
    ADD CONSTRAINT primary_attacks PRIMARY KEY (id);
ALTER TABLE ONLY attacks
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

CREATE INDEX index_attacks_dest ON attacks USING btree (dest);
CREATE UNIQUE INDEX index_attacks_id ON attacks USING btree (id);
ALTER TABLE attacks CLUSTER ON index_attacks_id;
CREATE INDEX index_attacks_sensorid ON attacks USING btree (sensorid);
CREATE INDEX index_attacks_severity ON attacks USING btree (severity);
CREATE INDEX index_attacks_source ON attacks USING btree (source);
CREATE INDEX index_attacks_timestamp ON attacks USING btree ("timestamp");

GRANT SELECT,INSERT,DELETE ON TABLE attacks TO idslog;
GRANT SELECT,INSERT,UPDATE ON TABLE attacks TO nepenthes;
GRANT SELECT,INSERT,UPDATE ON TABLE attacks TO argos;

GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO argos;

--
-- ARGOS_CSI
--

CREATE TABLE argos_csi (
    id integer NOT NULL,
    attacks_id integer,
    csi bit varying
);

CREATE SEQUENCE argos_csi_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE argos_csi_id_seq OWNED BY argos_csi.id;
ALTER TABLE argos_csi ALTER COLUMN id SET DEFAULT nextval('argos_csi_id_seq'::regclass);

--
-- ARGOS 
--

CREATE TABLE argos (
    id integer NOT NULL,
    sensorid integer,
    imageid integer,
    templateid integer,
    timespan character varying
);

CREATE SEQUENCE argos_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE argos_id_seq OWNED BY argos.id;
ALTER TABLE argos ALTER COLUMN id SET DEFAULT nextval('argos_id_seq'::regclass);
ALTER TABLE ONLY argos_csi
    ADD CONSTRAINT argos_csi_pkey PRIMARY KEY (id);
ALTER TABLE ONLY argos
    ADD CONSTRAINT primary_argos PRIMARY KEY (id);
ALTER TABLE ONLY argos
    ADD CONSTRAINT sensorid UNIQUE (sensorid);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos TO idslog;
GRANT SELECT ON TABLE argos TO argos;

GRANT SELECT,UPDATE ON SEQUENCE argos_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_id_seq TO argos;

--
-- ARGOS_IMAGES 
--

CREATE TABLE argos_images (
    id integer NOT NULL,
    name character varying,
    serverip inet,
    macaddr macaddr,
    imagename character varying,
    osname character varying,
    oslang character varying,
    organisationid integer DEFAULT 0
);

CREATE SEQUENCE argos_images_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE argos_images_id_seq OWNED BY argos_images.id;
ALTER TABLE argos_images ALTER COLUMN id SET DEFAULT nextval('argos_images_id_seq'::regclass);
ALTER TABLE ONLY argos_images
    ADD CONSTRAINT primary_argos_images PRIMARY KEY (id);
ALTER TABLE ONLY argos_images
    ADD CONSTRAINT unique_imagename UNIQUE (imagename);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_images TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_images TO argos;

GRANT SELECT,UPDATE ON SEQUENCE argos_images_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_images_id_seq TO argos;

--
-- ARGOS_RANGES 
--

CREATE TABLE argos_ranges (
    id integer NOT NULL,
    sensorid integer,
    range inet
);

CREATE SEQUENCE argos_ranges_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE argos_ranges_id_seq OWNED BY argos_ranges.id;
ALTER TABLE argos_ranges ALTER COLUMN id SET DEFAULT nextval('argos_ranges_id_seq'::regclass);
ALTER TABLE ONLY argos_ranges
    ADD CONSTRAINT argos_ranges_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_ranges TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE argos_ranges_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_ranges_id_seq TO argos;

--
-- ARGOS_TEMPLATES 
--

CREATE TABLE argos_templates (
    id integer NOT NULL,
    name character varying,
    abbr character varying
);

CREATE SEQUENCE argos_templates_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE argos_templates_id_seq OWNED BY argos_templates.id;
ALTER TABLE argos_templates ALTER COLUMN id SET DEFAULT nextval('argos_templates_id_seq'::regclass);
ALTER TABLE ONLY argos_templates
    ADD CONSTRAINT argos_templates_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_templates TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE argos_templates_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_templates_id_seq TO argos;

SELECT pg_catalog.setval('argos_templates_id_seq', 4, true);

INSERT INTO argos_templates VALUES (1, 'All Traffic', 'all');
INSERT INTO argos_templates VALUES (4, 'Top 100 sensor', 'top100sensor');
INSERT INTO argos_templates VALUES (3, 'Top 100 of all sensors', 'top100all');
INSERT INTO argos_templates VALUES (2, 'Top 100 of all your sensors', 'top100org');

--
-- ARP_CACHE 
--

CREATE TABLE arp_cache (
    id integer NOT NULL,
    mac macaddr NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL,
    last_seen integer NOT NULL,
    manufacturer character varying,
    flags character varying
);

CREATE SEQUENCE arp_cache_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE arp_cache_id_seq OWNED BY arp_cache.id;
ALTER TABLE arp_cache ALTER COLUMN id SET DEFAULT nextval('arp_cache_id_seq'::regclass);
ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT arp_cache_pkey PRIMARY KEY (id);
ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT unique_arpcache_ip_sensorid UNIQUE (ip, sensorid);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_cache TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE arp_cache_id_seq TO idslog;

--
-- ARP_EXCL 
--

CREATE TABLE arp_excl (
    id integer NOT NULL,
    mac macaddr NOT NULL
);

CREATE SEQUENCE arp_excl_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE arp_excl_id_seq OWNED BY arp_excl.id;
ALTER TABLE arp_excl ALTER COLUMN id SET DEFAULT nextval('arp_excl_id_seq'::regclass);
ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_mac_key UNIQUE (mac);
ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_excl TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE arp_excl_id_seq TO idslog;

--
-- ARP_STATIC 
--

CREATE TABLE arp_static (
    id integer NOT NULL,
    mac macaddr NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

CREATE SEQUENCE arp_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE arp_static_id_seq OWNED BY arp_static.id;
ALTER TABLE arp_static ALTER COLUMN id SET DEFAULT nextval('arp_static_id_seq'::regclass);
ALTER TABLE ONLY arp_static
    ADD CONSTRAINT arp_static_pkey PRIMARY KEY (id);
ALTER TABLE ONLY arp_static
    ADD CONSTRAINT unique_arp_static UNIQUE (mac, ip, sensorid);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_static TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE arp_static_id_seq TO idslog;

--
-- BINARIES 
--

CREATE TABLE binaries (
    id integer NOT NULL,
    "timestamp" integer,
    bin integer,
    info integer,
    scanner integer
);

CREATE SEQUENCE binaries_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE binaries_id_seq OWNED BY binaries.id;
ALTER TABLE binaries ALTER COLUMN id SET DEFAULT nextval('binaries_id_seq'::regclass);
ALTER TABLE ONLY binaries
    ADD CONSTRAINT primary_binaries PRIMARY KEY (id);
CREATE INDEX index_binaries ON binaries USING btree (bin);
CREATE INDEX index_binaries_info ON binaries USING btree (info);

GRANT SELECT,INSERT,UPDATE ON TABLE binaries TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE binaries_id_seq TO idslog;

--
-- BINARIES_DETAIL 
--

CREATE TABLE binaries_detail (
    id integer NOT NULL,
    bin integer,
    fileinfo character varying,
    filesize integer,
    last_scanned integer,
    upx character varying
);

CREATE SEQUENCE binaries_detail_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE binaries_detail_id_seq OWNED BY binaries_detail.id;
ALTER TABLE binaries_detail ALTER COLUMN id SET DEFAULT nextval('binaries_detail_id_seq'::regclass);
CREATE UNIQUE INDEX index_binaries_detail_bin ON binaries_detail USING btree (bin);
CREATE UNIQUE INDEX index_binaries_detail_id ON binaries_detail USING btree (id);
ALTER TABLE binaries_detail CLUSTER ON index_binaries_detail_id;

GRANT SELECT,INSERT,UPDATE ON TABLE binaries_detail TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE binaries_detail_id_seq TO idslog;

--
-- CWSANDBOX 
--

CREATE TABLE cwsandbox (
    binid integer NOT NULL,
    xml text,
    result text
);

ALTER TABLE ONLY cwsandbox
    ADD CONSTRAINT cwsandbox_binid_key UNIQUE (binid);

GRANT SELECT,INSERT,UPDATE ON TABLE cwsandbox TO idslog;

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
-- DETAILS 
--

CREATE TABLE details (
    id integer NOT NULL,
    attackid integer NOT NULL,
    sensorid integer NOT NULL,
    type integer NOT NULL,
    text text NOT NULL
);

CREATE SEQUENCE details_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE details_id_seq OWNED BY details.id;
ALTER TABLE details ALTER COLUMN id SET DEFAULT nextval('details_id_seq'::regclass);
ALTER TABLE ONLY details
    ADD CONSTRAINT primary_details PRIMARY KEY (id);
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_attack FOREIGN KEY (attackid) REFERENCES attacks(id) ON DELETE CASCADE;
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

CREATE INDEX index_details_attackid ON details USING btree (attackid);
CREATE UNIQUE INDEX index_details_id ON details USING btree (id);
ALTER TABLE details CLUSTER ON index_details_id;
CREATE INDEX index_details_sensorid ON details USING btree (sensorid);

GRANT SELECT,INSERT,DELETE ON TABLE details TO idslog;
GRANT SELECT,INSERT,UPDATE ON TABLE details TO nepenthes;
GRANT SELECT,INSERT,UPDATE ON TABLE details TO argos;

GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO argos;

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
-- INDEXMODS 
--

CREATE TABLE indexmods (
    id integer NOT NULL,
    phppage character varying
);

CREATE SEQUENCE indexmods_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE indexmods_id_seq OWNED BY indexmods.id;
ALTER TABLE indexmods ALTER COLUMN id SET DEFAULT nextval('indexmods_id_seq'::regclass);
ALTER TABLE ONLY indexmods
    ADD CONSTRAINT indexmods_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE indexmods TO idslog;

SELECT pg_catalog.setval('indexmods_id_seq', 11, true);

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

--
-- LOGIN 
--

CREATE TABLE login (
    id integer NOT NULL,
    username character varying NOT NULL,
    password character varying NOT NULL,
    email character varying,
    lastlogin integer,
    organisation integer DEFAULT 0 NOT NULL,
    access character varying DEFAULT '000'::character varying NOT NULL,
    serverhash character varying,
    gpg integer DEFAULT 0,
    d_plotter integer DEFAULT 0 NOT NULL,
    d_plottype integer DEFAULT 1 NOT NULL,
    d_utc integer DEFAULT 0 NOT NULL
);

CREATE SEQUENCE login_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE login_id_seq OWNED BY login.id;
ALTER TABLE login ALTER COLUMN id SET DEFAULT nextval('login_id_seq'::regclass);
ALTER TABLE ONLY login
    ADD CONSTRAINT primary_login PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE login TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE login_id_seq TO idslog;

INSERT INTO login (id, username, password, email, organisation, access) VALUES (nextval('login_id_seq'::regclass), 'admin',  '21232f297a57a5a743894a0e4a801fc3', 'root@localhost', 1, '999');
SELECT pg_catalog.setval('login_id_seq', 1, true);

--
-- INDEXMODS_SELECTED 
--

CREATE TABLE indexmods_selected (
    login_id integer,
    indexmod_id integer
);
ALTER TABLE ONLY indexmods_selected
    ADD CONSTRAINT foreign_indexmods FOREIGN KEY (login_id) REFERENCES login(id) ON DELETE CASCADE;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE indexmods_selected TO idslog;

--
-- NORMAN 
--

CREATE TABLE norman (
    binid integer NOT NULL,
    result text
);

ALTER TABLE ONLY norman
    ADD CONSTRAINT norman_binid_key UNIQUE (binid);

GRANT SELECT,INSERT,UPDATE ON TABLE norman TO idslog;

--
-- ORG_EXCL 
--

CREATE TABLE org_excl (
    id integer NOT NULL,
    orgid integer NOT NULL,
    exclusion inet NOT NULL
);

CREATE SEQUENCE org_excl_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE org_excl_id_seq OWNED BY org_excl.id;
ALTER TABLE org_excl ALTER COLUMN id SET DEFAULT nextval('org_excl_id_seq'::regclass);
ALTER TABLE ONLY org_excl
    ADD CONSTRAINT org_excl_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE org_excl TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE org_excl_id_seq TO idslog;

--
-- ORG_ID 
--

CREATE TABLE org_id (
    id integer NOT NULL,
    orgid integer NOT NULL,
    identifier character varying NOT NULL,
    type integer
);

CREATE SEQUENCE org_id_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE org_id_id_seq OWNED BY org_id.id;
ALTER TABLE org_id ALTER COLUMN id SET DEFAULT nextval('org_id_id_seq'::regclass);
ALTER TABLE ONLY org_id
    ADD CONSTRAINT primary_org_id_id PRIMARY KEY (id);
ALTER TABLE ONLY org_id
    ADD CONSTRAINT unique_identifier UNIQUE (identifier);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE org_id TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE org_id_id_seq TO idslog;

--
-- ORGANISATIONS 
--

CREATE TABLE organisations (
    id integer NOT NULL,
    organisation character varying NOT NULL,
    ranges text
);

CREATE SEQUENCE organisations_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE organisations_id_seq OWNED BY organisations.id;
ALTER TABLE organisations ALTER COLUMN id SET DEFAULT nextval('organisations_id_seq'::regclass);
ALTER TABLE ONLY organisations
    ADD CONSTRAINT primary_organisations PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE organisations TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE organisations_id_seq TO idslog;

INSERT INTO organisations VALUES (1, 'ADMIN', '');
SELECT pg_catalog.setval('organisations_id_seq', 1, true);

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
    ADD CONSTRAINT foreign_pageconf FOREIGN KEY (userid) REFERENCES login(id) ON DELETE CASCADE;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE pageconf TO idslog;

--
-- REPORT_CONTENT
--

CREATE TABLE report_content (
    id integer NOT NULL,
    user_id integer,
    template integer,
    last_sent integer,
    active boolean DEFAULT true NOT NULL,
    sensor_id integer,
    frequency integer,
    "interval" integer DEFAULT (-1) NOT NULL,
    priority integer,
    subject character varying,
    operator integer DEFAULT (-1) NOT NULL,
    threshold integer DEFAULT (-1) NOT NULL,
    severity integer DEFAULT (-1) NOT NULL,
    detail integer DEFAULT 0 NOT NULL,
    qs character varying,
    from_ts integer DEFAULT (-1) NOT NULL,
    to_ts integer DEFAULT (-1) NOT NULL,
    always integer DEFAULT 0 NOT NULL,
    utc integer DEFAULT 0 NOT NULL
);

CREATE SEQUENCE report_content_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE report_content_id_seq OWNED BY report_content.id;
ALTER TABLE report_content ALTER COLUMN id SET DEFAULT nextval('report_content_id_seq'::regclass);
ALTER TABLE ONLY report_content
    ADD CONSTRAINT primary_report_content PRIMARY KEY (id);
ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES login(id) ON DELETE CASCADE;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE report_content TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE report_content_id_seq TO idslog;

--
-- RRD 
--

CREATE TABLE rrd (
    id integer NOT NULL,
    orgid integer NOT NULL,
    type character varying NOT NULL,
    label character varying NOT NULL,
    image character varying NOT NULL,
    "timestamp" integer
);

CREATE SEQUENCE rrd_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE rrd_id_seq OWNED BY rrd.id;
ALTER TABLE rrd ALTER COLUMN id SET DEFAULT nextval('rrd_id_seq'::regclass);
ALTER TABLE ONLY rrd
    ADD CONSTRAINT primary_rrd PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE rrd TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE rrd_id_seq TO idslog;

--
-- SCANNERS 
--

CREATE TABLE scanners (
    id integer NOT NULL,
    name character varying,
    status integer DEFAULT 0 NOT NULL,
    version character varying,
    getvirus character varying,
    matchvirus character varying,
    getbin character varying,
    matchclean character varying
);

CREATE SEQUENCE scanners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE scanners_id_seq OWNED BY scanners.id;
ALTER TABLE scanners ALTER COLUMN id SET DEFAULT nextval('scanners_id_seq'::regclass);
ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);

GRANT SELECT,UPDATE ON TABLE scanners TO idslog;

GRANT SELECT ON SEQUENCE scanners_id_seq TO idslog;

SELECT pg_catalog.setval('scanners_id_seq', 6, true);

INSERT INTO scanners VALUES (2, 'Antivir', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (3, 'BitDefender', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (6, 'Kaspersky', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (1, 'ClamAV', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (4, 'AVAST', 0, '', E'.*\\[infected by: *(.*) *\\[.*\\]\\]$', E'.*\\[infected by:.*', E'.*\\/([0-9A-Za-z]*).*\\[.*\\]$', E'.*\\[OK\\]$');
INSERT INTO scanners VALUES (5, 'F-Prot', 0, '', E'.*\\[Found .*\\].*<(.*)> {1,}.*', E'.*\\[Found .*\\].*', E'.*\\[.*\\] {1,}.*([a-zA-Z0-9]{32}).*', E'.*\\[Clean\\].*');

--
-- SCHEME 
--

CREATE TABLE scheme (
    version integer NOT NULL,
    created timestamp with time zone NOT NULL
);

ALTER TABLE ONLY scheme
    ADD CONSTRAINT scheme_pkey PRIMARY KEY (version);

GRANT SELECT ON TABLE scheme TO pofuser;

INSERT INTO scheme (version, created) VALUES (1002, CURRENT_TIMESTAMP);

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
-- SERVERSTATS 
--

CREATE TABLE serverstats (
    id integer NOT NULL,
    "timestamp" integer NOT NULL,
    type character varying NOT NULL,
    label character varying NOT NULL,
    "interval" character varying NOT NULL,
    image character varying NOT NULL,
    server character varying NOT NULL
);

CREATE SEQUENCE serverstats_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE serverstats_id_seq OWNED BY serverstats.id;
ALTER TABLE serverstats ALTER COLUMN id SET DEFAULT nextval('serverstats_id_seq'::regclass);
ALTER TABLE ONLY serverstats
    ADD CONSTRAINT primary_serverstats PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE serverstats TO idslog;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE serverstats TO argos;

GRANT SELECT,UPDATE ON SEQUENCE serverstats_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE serverstats_id_seq TO argos;

--
-- SESSIONS
--

CREATE TABLE sessions (
    id integer NOT NULL,
    sid character varying NOT NULL,
    ip inet NOT NULL,
    ts integer NOT NULL,
    username integer,
    useragent character varying
);

CREATE SEQUENCE sessions_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sessions_id_seq OWNED BY sessions.id;
ALTER TABLE sessions ALTER COLUMN id SET DEFAULT nextval('sessions_id_seq'::regclass);
ALTER TABLE ONLY sessions
    ADD CONSTRAINT primary_sessions PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sessions TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE sessions_id_seq TO idslog;

--
-- SEVERITY
--

CREATE TABLE severity (
    id integer NOT NULL,
    val integer NOT NULL,
    txt character varying NOT NULL
);

ALTER TABLE ONLY severity
    ADD CONSTRAINT primary_severity PRIMARY KEY (id);
ALTER TABLE ONLY severity
    ADD CONSTRAINT unique_severity UNIQUE (val);

GRANT SELECT ON TABLE severity TO idslog;

INSERT INTO severity VALUES (1, 0, 'Possible malicious attack');
INSERT INTO severity VALUES (2, 1, 'Malicious attack');
INSERT INTO severity VALUES (3, 16, 'Malware offered');
INSERT INTO severity VALUES (4, 32, 'Malware downloaded');

--
-- SNIFF_HOSTTYPES
--

CREATE TABLE sniff_hosttypes (
    id integer NOT NULL,
    staticid integer NOT NULL,
    type integer NOT NULL
);

CREATE SEQUENCE sniff_hosttypes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sniff_hosttypes_id_seq OWNED BY sniff_hosttypes.id;
ALTER TABLE sniff_hosttypes ALTER COLUMN id SET DEFAULT nextval('sniff_hosttypes_id_seq'::regclass);
ALTER TABLE ONLY sniff_hosttypes
    ADD CONSTRAINT sniff_hosttypes_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sniff_hosttypes TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE sniff_hosttypes_id_seq TO idslog;

--
-- SNIFF_PROTOS
--

CREATE TABLE sniff_protos (
    id integer NOT NULL,
    sensorid integer NOT NULL,
    parent integer NOT NULL,
    number integer NOT NULL,
    protocol character varying NOT NULL
);

CREATE SEQUENCE sniff_protos_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE sniff_protos_id_seq OWNED BY sniff_protos.id;
ALTER TABLE sniff_protos ALTER COLUMN id SET DEFAULT nextval('sniff_protos_id_seq'::regclass);
ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT sniff_protos_pkey PRIMARY KEY (id);
ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT unique_sniff_protos UNIQUE (sensorid, parent, number);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sniff_protos TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE sniff_protos_id_seq TO idslog;

--
-- STATS_DIALOGUE
--

CREATE TABLE stats_dialogue (
    id integer NOT NULL,
    "desc" character varying,
    url character varying,
    name character varying
);

CREATE SEQUENCE stats_dialogue_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE stats_dialogue_id_seq OWNED BY stats_dialogue.id;
ALTER TABLE stats_dialogue ALTER COLUMN id SET DEFAULT nextval('stats_dialogue_id_seq'::regclass);
ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT primary_stats_dialogue PRIMARY KEY (id);
ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT unique_stats_dialogue UNIQUE (name);

GRANT SELECT ON TABLE stats_dialogue TO idslog;
GRANT SELECT,INSERT ON TABLE stats_dialogue TO nepenthes;

GRANT SELECT,UPDATE ON SEQUENCE stats_dialogue_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE stats_dialogue_id_seq TO nepenthes;

--
-- STATS_VIRUS
--

CREATE TABLE stats_virus (
    id integer NOT NULL,
    name character varying
);

CREATE SEQUENCE stats_virus_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE stats_virus_id_seq OWNED BY stats_virus.id;
ALTER TABLE stats_virus ALTER COLUMN id SET DEFAULT nextval('stats_virus_id_seq'::regclass);
ALTER TABLE ONLY stats_virus
    ADD CONSTRAINT primary_stats_virus PRIMARY KEY (id);

GRANT SELECT,INSERT ON TABLE stats_virus TO idslog;

GRANT SELECT,UPDATE ON SEQUENCE stats_virus_id_seq TO idslog;

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
-- SYSTEM
--

CREATE TABLE system (
    sid bigint NOT NULL,
    ip_addr inet NOT NULL,
    name character varying(128) NOT NULL,
    first_tstamp timestamp with time zone,
    last_tstamp timestamp with time zone NOT NULL
);

CREATE SEQUENCE system_sid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE system_sid_seq OWNED BY system.sid;
ALTER TABLE system ALTER COLUMN sid SET DEFAULT nextval('system_sid_seq'::regclass);
ALTER TABLE ONLY system
    ADD CONSTRAINT system_ip_addr_key UNIQUE (ip_addr, name);
ALTER TABLE ONLY system
    ADD CONSTRAINT system_pkey PRIMARY KEY (sid);
CREATE INDEX first_tstamp_index ON system USING btree (first_tstamp);
CREATE INDEX ip_addr_name_index ON system USING btree (ip_addr, name);
CREATE INDEX last_tstamp_index ON system USING btree (last_tstamp);

GRANT SELECT ON TABLE system TO idslog;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE system TO pofuser;

GRANT SELECT,UPDATE ON SEQUENCE system_sid_seq TO pofuser;

--
-- OSTYPES 
--

CREATE TABLE ostypes (
    id integer NOT NULL,
    os character varying NOT NULL
);


CREATE SEQUENCE ostypes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ostypes_id_seq OWNED BY ostypes.id;
ALTER TABLE ostypes ALTER COLUMN id SET DEFAULT nextval('ostypes_id_seq'::regclass);
ALTER TABLE ONLY ostypes
    ADD CONSTRAINT ostypes_pkey PRIMARY KEY (id);
ALTER TABLE ONLY ostypes
    ADD CONSTRAINT unique_os UNIQUE (os);

CREATE RULE insert_name AS ON INSERT TO system WHERE (NOT (split_part((new.name)::text, ' '::text, 1) IN (SELECT ostypes.os FROM ostypes))) DO INSERT INTO ostypes (os) VALUES (split_part((new.name)::text, ' '::text, 1));

GRANT SELECT,INSERT ON TABLE ostypes TO pofuser;
GRANT SELECT ON TABLE ostypes TO idslog;

GRANT SELECT ON SEQUENCE ostypes_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ostypes_id_seq TO pofuser;

--
--  SYSTEM_DETAILS
--

CREATE TABLE system_details (
    sid integer NOT NULL,
    ip_addr inet NOT NULL,
    nat character varying(64) DEFAULT 'no/unknown'::character varying NOT NULL,
    ecn character varying(64) DEFAULT 'no/unknown'::character varying NOT NULL,
    firewall character varying(64) DEFAULT 'no/unknown'::character varying NOT NULL,
    lookup_link character varying(128) DEFAULT 'unknown'::character varying NOT NULL,
    distance smallint DEFAULT 0 NOT NULL
);
CREATE INDEX sid_index ON system_details USING btree (sid);
CREATE INDEX system_details_ip_addr_index ON system_details USING btree (ip_addr);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE system_details TO pofuser;

--
-- UNIQ_BINARIES 
--

CREATE TABLE uniq_binaries (
    id integer NOT NULL,
    name character varying
);

CREATE SEQUENCE uniq_binaries_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE uniq_binaries_id_seq OWNED BY uniq_binaries.id;
ALTER TABLE uniq_binaries ALTER COLUMN id SET DEFAULT nextval('uniq_binaries_id_seq'::regclass);
ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT uniq_binaries_pkey PRIMARY KEY (id);
ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT unique_bin UNIQUE (name);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE uniq_binaries TO idslog;
GRANT SELECT,INSERT ON TABLE uniq_binaries TO nepenthes;

GRANT SELECT,UPDATE ON SEQUENCE uniq_binaries_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE uniq_binaries_id_seq TO nepenthes;

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
-- FUNCTION surfnet_attack_add(severity, sourceip, sourceport, destip, destport, sourcemac, tapip)
--

CREATE FUNCTION surfnet_attack_add(integer, inet, integer, inet, integer, macaddr, inet) RETURNS integer
    AS $_$DECLARE
	p_severity	ALIAS FOR $1; 
	p_attackerip	ALIAS FOR $2;
	p_attackerport	ALIAS FOR $3;
	p_decoyip	ALIAS FOR $4;
	p_decoyport	ALIAS FOR $5;
	p_hwa		ALIAS FOR $6;
	p_localhost	ALIAS FOR $7;
	m_attackid INTEGER;
	m_sensorid INTEGER;
BEGIN

	SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
	SELECT INTO m_attackid surfnet_attack_add_by_id(p_severity,
		p_attackerip, p_attackerport, p_decoyip,
		p_decoyport, p_hwa, m_sensorid);

	return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_attack_add_by_id(severity, sourceip, sourceport, destip, destport, sourcemac, sensorid)
--

CREATE FUNCTION surfnet_attack_add_by_id(integer, inet, integer, inet, integer, macaddr, integer) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_attackerip    ALIAS FOR $2;
        p_attackerport  ALIAS FOR $3;
        p_decoyip       ALIAS FOR $4;
        p_decoyport     ALIAS FOR $5;
        p_hwa           ALIAS FOR $6;
        p_sensorid      ALIAS FOR $7;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks
                (severity,
                 timestamp,
                 source,
                 sport,
                 dest,
                 dport,
                 sensorid,
                 src_mac)
        VALUES
                (p_severity,
                 extract(epoch from current_timestamp(0))::integer,
                 p_attackerip,
                 p_attackerport,
                 p_decoyip,
                 p_decoyport,
                 p_sensorid,
                 p_hwa);

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_attack_update_severity(attackid, newseverity)
--

CREATE FUNCTION surfnet_attack_update_severity(integer, integer) RETURNS void
    AS $_$DECLARE
        p_attackid ALIAS FOR $1;
        p_severity ALIAS FOR $2;
BEGIN
        UPDATE attacks SET severity = p_severity WHERE id = p_attackid;
        return;
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_detail_add(attackid, tapip, type, data)
--

CREATE FUNCTION surfnet_detail_add(integer, inet, integer, character varying) RETURNS void
    AS $_$DECLARE
	p_attackid ALIAS FOR $1;
	p_localhost ALIAS FOR $2;
	p_type ALIAS FOR $3;
	p_data ALIAS FOR $4;

	m_sensorid INTEGER;
	m_check INTEGER;
BEGIN
	SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);

        IF p_type = 1 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = p_data;
          IF m_check = 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

	INSERT INTO details
		(attackid,sensorid,type,text)
	VALUES
		(p_attackid,m_sensorid,p_type,p_data);
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_detail_add_by_id(attackid, sensorid, type, data)
--

CREATE FUNCTION surfnet_detail_add_by_id(integer, integer, integer, character varying) RETURNS void
    AS $_$DECLARE
	p_attackid ALIAS FOR $1;
	m_sensorid ALIAS FOR $2;
	p_type ALIAS FOR $3;
	p_data ALIAS FOR $4;

	m_check INTEGER;
BEGIN
        IF p_type = 1 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = p_data;
          IF m_check = 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

	INSERT INTO details
		(attackid,sensorid,type,text)
	VALUES
		(p_attackid,m_sensorid,p_type,p_data);
END$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_detail_add_download(sourceip, tapip, url, hash)
--

CREATE FUNCTION surfnet_detail_add_download(inet, inet, character varying, character varying) RETURNS void
    AS $_$DECLARE
	p_remotehost ALIAS FOR $1;
	p_localhost ALIAS FOR $2;
	p_url ALIAS FOR $3;
	p_hash ALIAS FOR $4;

	m_sensorid INTEGER;
	m_attackid INTEGER;
	m_check INTEGER;
BEGIN
	SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
	SELECT INTO m_attackid surfnet_attack_add_by_id(32,p_remotehost, 0,
		p_localhost, 0,
		NULL,m_sensorid);

        SELECT COUNT(name) INTO m_check FROM uniq_binaries WHERE name = p_hash;

        IF m_check = 0 THEN
          INSERT INTO uniq_binaries (name) VALUES (p_hash); 
        END IF;

	PERFORM surfnet_detail_add_by_id(m_attackid,
				m_sensorid,4,p_url);
	PERFORM surfnet_detail_add_by_id(m_attackid,
				m_sensorid,8,p_hash);

	return;
END;	$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_detail_add_offer(sourceip, tapip, url)
--

CREATE FUNCTION surfnet_detail_add_offer(inet, inet, character varying) RETURNS void
    AS $_$DECLARE
	p_remotehost ALIAS FOR $1;
	p_localhost ALIAS FOR $2;
	p_url ALIAS FOR $3;

	m_sensorid INTEGER;
	m_attackid INTEGER;
BEGIN
	SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
	SELECT INTO m_attackid surfnet_attack_add_by_id(16,p_remotehost, 0,
		p_localhost, 0,
		NULL,m_sensorid);

	PERFORM surfnet_detail_add_by_id(m_attackid,
				m_sensorid,4,p_url);
	return;
END;	$_$
    LANGUAGE plpgsql;

--
-- FUNCTION surfnet_sensorid_get(tapip)
--

CREATE FUNCTION surfnet_sensorid_get(inet) RETURNS integer
    AS $_$DECLARE
  p_localhost ALIAS FOR $1;
  m_sensorid  INTEGER;
BEGIN
        SELECT INTO m_sensorid id FROM sensors WHERE tapip = p_localhost;
        return m_sensorid;
END
$_$
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
