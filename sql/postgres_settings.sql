--
-- SURFids 2.10
-- Database structure
-- Changeset 005
-- 25-02-2009
--

--
-- Version history
-- 005 A fresh schema of the development database
-- 004 Modified report_content, added pageconf, indexmods
-- 003 Added ostypes & modified system, login, report_content
-- 002 Added arp_excl
-- 001 Initial release
--

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
    d_utc integer DEFAULT 0 NOT NULL,
    d_sensorstatus character varying
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

SELECT pg_catalog.setval('scanners_id_seq', 1, false);

INSERT INTO scanners VALUES (2, 'Antivir', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (3, 'BitDefender', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (6, 'Kaspersky', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (1, 'ClamAV', 0, '', NULL, NULL, NULL, NULL);
INSERT INTO scanners VALUES (4, 'AVAST', 1, 'v1.0.8', '.*\\[infected by: *(.*) *\\[.*\\]\\]$', '.*\\[infected by:.*', '.*\\/([0-9A-Za-z]*).*\\[.*\\]$', '.*\\[OK\\]$');
INSERT INTO scanners VALUES (5, 'F-Prot', 1, '6.2.1.4252', '.*\\[Found .*\\].*<(.*)> {1,}.*', '.*\\[Found .*\\].*', '.*\\[.*\\] {1,}.*([a-zA-Z0-9]{32}).*', '.*\\[Clean\\].*');

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

--
-- Name: argos_csi_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE argos_csi_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.argos_csi_id_seq OWNER TO surfnet;

--
-- Name: argos_csi_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE argos_csi_id_seq OWNED BY argos_csi.id;


--
-- Name: argos_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE argos_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.argos_id_seq OWNER TO surfnet;

--
-- Name: argos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE argos_id_seq OWNED BY argos.id;


--
-- Name: argos_images_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE argos_images_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.argos_images_id_seq OWNER TO surfnet;

--
-- Name: argos_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE argos_images_id_seq OWNED BY argos_images.id;


--
-- Name: argos_ranges_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE argos_ranges_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.argos_ranges_id_seq OWNER TO surfnet;

--
-- Name: argos_ranges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE argos_ranges_id_seq OWNED BY argos_ranges.id;


--
-- Name: argos_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE argos_templates_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.argos_templates_id_seq OWNER TO surfnet;

--
-- Name: argos_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE argos_templates_id_seq OWNED BY argos_templates.id;


--
-- Name: arp_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE arp_cache_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.arp_cache_id_seq OWNER TO surfnet;

--
-- Name: arp_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE arp_cache_id_seq OWNED BY arp_cache.id;


--
-- Name: arp_excl_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE arp_excl_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.arp_excl_id_seq OWNER TO surfnet;

--
-- Name: arp_excl_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE arp_excl_id_seq OWNED BY arp_excl.id;


--
-- Name: arp_static_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE arp_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.arp_static_id_seq OWNER TO surfnet;

--
-- Name: arp_static_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE arp_static_id_seq OWNED BY arp_static.id;


--
-- Name: attacks_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE attacks_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.attacks_id_seq OWNER TO postgres;

--
-- Name: attacks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE attacks_id_seq OWNED BY attacks.id;


--
-- Name: binaries_detail_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE binaries_detail_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.binaries_detail_id_seq OWNER TO postgres;

--
-- Name: binaries_detail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE binaries_detail_id_seq OWNED BY binaries_detail.id;


--
-- Name: binaries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE binaries_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.binaries_id_seq OWNER TO postgres;

--
-- Name: binaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE binaries_id_seq OWNED BY binaries.id;


--
-- Name: deactivated_attacks_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE deactivated_attacks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.deactivated_attacks_id_seq OWNER TO surfnet;

--
-- Name: deactivated_attacks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE deactivated_attacks_id_seq OWNED BY deactivated_attacks.id;


--
-- Name: deactivated_details_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE deactivated_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.deactivated_details_id_seq OWNER TO surfnet;

--
-- Name: deactivated_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE deactivated_details_id_seq OWNED BY deactivated_details.id;


--
-- Name: deactivated_sensors_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE deactivated_sensors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.deactivated_sensors_id_seq OWNER TO surfnet;

--
-- Name: deactivated_sensors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE deactivated_sensors_id_seq OWNED BY deactivated_sensors.id;


--
-- Name: details_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE details_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.details_id_seq OWNER TO postgres;

--
-- Name: details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE details_id_seq OWNED BY details.id;


--
-- Name: groupmembers_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE groupmembers_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.groupmembers_id_seq OWNER TO surfnet;

--
-- Name: groupmembers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE groupmembers_id_seq OWNED BY groupmembers.id;


--
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE groups_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.groups_id_seq OWNER TO surfnet;

--
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE groups_id_seq OWNED BY groups.id;


--
-- Name: indexmods_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE indexmods_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.indexmods_id_seq OWNER TO surfnet;

--
-- Name: indexmods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE indexmods_id_seq OWNED BY indexmods.id;


--
-- Name: login_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE login_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.login_id_seq OWNER TO postgres;

--
-- Name: login_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE login_id_seq OWNED BY login.id;


--
-- Name: org_excl_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE org_excl_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.org_excl_id_seq OWNER TO surfnet;

--
-- Name: org_excl_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE org_excl_id_seq OWNED BY org_excl.id;


--
-- Name: org_id_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE org_id_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.org_id_id_seq OWNER TO postgres;

--
-- Name: org_id_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE org_id_id_seq OWNED BY org_id.id;


--
-- Name: organisations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE organisations_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.organisations_id_seq OWNER TO postgres;

--
-- Name: organisations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE organisations_id_seq OWNED BY organisations.id;


--
-- Name: ostypes_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE ostypes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ostypes_id_seq OWNER TO surfnet;

--
-- Name: ostypes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE ostypes_id_seq OWNED BY ostypes.id;


--
-- Name: report_content_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_content_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.report_content_id_seq OWNER TO postgres;

--
-- Name: report_content_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE report_content_id_seq OWNED BY report_content.id;


--
-- Name: rrd_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE rrd_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.rrd_id_seq OWNER TO postgres;

--
-- Name: rrd_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE rrd_id_seq OWNED BY rrd.id;


--
-- Name: scanners_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE scanners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.scanners_id_seq OWNER TO postgres;

--
-- Name: scanners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE scanners_id_seq OWNED BY scanners.id;


--
-- Name: sensor_details_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE sensor_details_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sensor_details_id_seq OWNER TO surfnet;

--
-- Name: sensor_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE sensor_details_id_seq OWNED BY sensor_details.id;


--
-- Name: sensor_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE sensor_notes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sensor_notes_id_seq OWNER TO surfnet;

--
-- Name: sensor_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE sensor_notes_id_seq OWNED BY sensor_notes.id;


--
-- Name: sensors_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE sensors_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sensors_id_seq OWNER TO postgres;

--
-- Name: sensors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE sensors_id_seq OWNED BY sensors.id;


--
-- Name: serverstats_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE serverstats_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.serverstats_id_seq OWNER TO postgres;

--
-- Name: serverstats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE serverstats_id_seq OWNED BY serverstats.id;


--
-- Name: sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE sessions_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sessions_id_seq OWNER TO postgres;

--
-- Name: sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE sessions_id_seq OWNED BY sessions.id;


--
-- Name: sniff_hosttypes_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE sniff_hosttypes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sniff_hosttypes_id_seq OWNER TO surfnet;

--
-- Name: sniff_hosttypes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE sniff_hosttypes_id_seq OWNED BY sniff_hosttypes.id;


--
-- Name: sniff_protos_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE sniff_protos_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sniff_protos_id_seq OWNER TO surfnet;

--
-- Name: sniff_protos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE sniff_protos_id_seq OWNED BY sniff_protos.id;


--
-- Name: stats_dialogue_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE stats_dialogue_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.stats_dialogue_id_seq OWNER TO postgres;

--
-- Name: stats_dialogue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE stats_dialogue_id_seq OWNED BY stats_dialogue.id;


--
-- Name: stats_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE stats_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.stats_history_id_seq OWNER TO postgres;

--
-- Name: stats_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE stats_history_id_seq OWNED BY stats_history.id;


--
-- Name: stats_virus_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE stats_virus_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.stats_virus_id_seq OWNER TO postgres;

--
-- Name: stats_virus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE stats_virus_id_seq OWNED BY stats_virus.id;


--
-- Name: syslog_id_seq; Type: SEQUENCE; Schema: public; Owner: surfnet
--

CREATE SEQUENCE syslog_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.syslog_id_seq OWNER TO surfnet;

--
-- Name: syslog_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: surfnet
--

ALTER SEQUENCE syslog_id_seq OWNED BY syslog.id;


--
-- Name: system_sid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE system_sid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.system_sid_seq OWNER TO postgres;

--
-- Name: system_sid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE system_sid_seq OWNED BY system.sid;


--
-- Name: uniq_binaries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE uniq_binaries_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.uniq_binaries_id_seq OWNER TO postgres;

--
-- Name: uniq_binaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE uniq_binaries_id_seq OWNED BY uniq_binaries.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE argos ALTER COLUMN id SET DEFAULT nextval('argos_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE argos_csi ALTER COLUMN id SET DEFAULT nextval('argos_csi_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE argos_images ALTER COLUMN id SET DEFAULT nextval('argos_images_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE argos_ranges ALTER COLUMN id SET DEFAULT nextval('argos_ranges_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE argos_templates ALTER COLUMN id SET DEFAULT nextval('argos_templates_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE arp_cache ALTER COLUMN id SET DEFAULT nextval('arp_cache_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE arp_excl ALTER COLUMN id SET DEFAULT nextval('arp_excl_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE arp_static ALTER COLUMN id SET DEFAULT nextval('arp_static_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE attacks ALTER COLUMN id SET DEFAULT nextval('attacks_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE binaries ALTER COLUMN id SET DEFAULT nextval('binaries_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE binaries_detail ALTER COLUMN id SET DEFAULT nextval('binaries_detail_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE deactivated_attacks ALTER COLUMN id SET DEFAULT nextval('deactivated_attacks_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE deactivated_details ALTER COLUMN id SET DEFAULT nextval('deactivated_details_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE deactivated_sensors ALTER COLUMN id SET DEFAULT nextval('deactivated_sensors_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE details ALTER COLUMN id SET DEFAULT nextval('details_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE groupmembers ALTER COLUMN id SET DEFAULT nextval('groupmembers_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE groups ALTER COLUMN id SET DEFAULT nextval('groups_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE indexmods ALTER COLUMN id SET DEFAULT nextval('indexmods_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE login ALTER COLUMN id SET DEFAULT nextval('login_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE org_excl ALTER COLUMN id SET DEFAULT nextval('org_excl_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE org_id ALTER COLUMN id SET DEFAULT nextval('org_id_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE organisations ALTER COLUMN id SET DEFAULT nextval('organisations_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE ostypes ALTER COLUMN id SET DEFAULT nextval('ostypes_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE report_content ALTER COLUMN id SET DEFAULT nextval('report_content_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE rrd ALTER COLUMN id SET DEFAULT nextval('rrd_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE scanners ALTER COLUMN id SET DEFAULT nextval('scanners_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE sensor_details ALTER COLUMN id SET DEFAULT nextval('sensor_details_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE sensor_notes ALTER COLUMN id SET DEFAULT nextval('sensor_notes_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE sensors ALTER COLUMN id SET DEFAULT nextval('sensors_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE serverstats ALTER COLUMN id SET DEFAULT nextval('serverstats_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE sessions ALTER COLUMN id SET DEFAULT nextval('sessions_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE sniff_hosttypes ALTER COLUMN id SET DEFAULT nextval('sniff_hosttypes_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE sniff_protos ALTER COLUMN id SET DEFAULT nextval('sniff_protos_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE stats_dialogue ALTER COLUMN id SET DEFAULT nextval('stats_dialogue_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE stats_history ALTER COLUMN id SET DEFAULT nextval('stats_history_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE stats_virus ALTER COLUMN id SET DEFAULT nextval('stats_virus_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: surfnet
--

ALTER TABLE syslog ALTER COLUMN id SET DEFAULT nextval('syslog_id_seq'::regclass);


--
-- Name: sid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE system ALTER COLUMN sid SET DEFAULT nextval('system_sid_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE uniq_binaries ALTER COLUMN id SET DEFAULT nextval('uniq_binaries_id_seq'::regclass);


--
-- Name: argos_csi_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos_csi
    ADD CONSTRAINT argos_csi_pkey PRIMARY KEY (id);


--
-- Name: argos_ranges_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos_ranges
    ADD CONSTRAINT argos_ranges_pkey PRIMARY KEY (id);


--
-- Name: argos_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos_templates
    ADD CONSTRAINT argos_templates_pkey PRIMARY KEY (id);


--
-- Name: arp_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT arp_cache_pkey PRIMARY KEY (id);


--
-- Name: arp_excl_mac_key; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_mac_key UNIQUE (mac);


--
-- Name: arp_excl_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_excl
    ADD CONSTRAINT arp_excl_pkey PRIMARY KEY (id);


--
-- Name: arp_static_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_static
    ADD CONSTRAINT arp_static_pkey PRIMARY KEY (id);


--
-- Name: cwsandbox_binid_key; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY cwsandbox
    ADD CONSTRAINT cwsandbox_binid_key UNIQUE (binid);


--
-- Name: groupmembers_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_pkey PRIMARY KEY (id);


--
-- Name: groups_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: indexmods_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY indexmods
    ADD CONSTRAINT indexmods_pkey PRIMARY KEY (id);


--
-- Name: logmessages_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY logmessages
    ADD CONSTRAINT logmessages_pkey PRIMARY KEY (id);


--
-- Name: norman_binid_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY norman
    ADD CONSTRAINT norman_binid_key UNIQUE (binid);


--
-- Name: org_excl_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY org_excl
    ADD CONSTRAINT org_excl_pkey PRIMARY KEY (id);


--
-- Name: ostypes_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY ostypes
    ADD CONSTRAINT ostypes_pkey PRIMARY KEY (id);


--
-- Name: primary_argos; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos
    ADD CONSTRAINT primary_argos PRIMARY KEY (id);


--
-- Name: primary_argos_images; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos_images
    ADD CONSTRAINT primary_argos_images PRIMARY KEY (id);


--
-- Name: primary_attacks; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attacks
    ADD CONSTRAINT primary_attacks PRIMARY KEY (id);


--
-- Name: primary_binaries; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY binaries
    ADD CONSTRAINT primary_binaries PRIMARY KEY (id);


--
-- Name: primary_details; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY details
    ADD CONSTRAINT primary_details PRIMARY KEY (id);


--
-- Name: primary_login; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY login
    ADD CONSTRAINT primary_login PRIMARY KEY (id);


--
-- Name: primary_org_id_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY org_id
    ADD CONSTRAINT primary_org_id_id PRIMARY KEY (id);


--
-- Name: primary_organisations; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY organisations
    ADD CONSTRAINT primary_organisations PRIMARY KEY (id);


--
-- Name: primary_report_content; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_content
    ADD CONSTRAINT primary_report_content PRIMARY KEY (id);


--
-- Name: primary_rrd; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY rrd
    ADD CONSTRAINT primary_rrd PRIMARY KEY (id);


--
-- Name: primary_sensors; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY sensors
    ADD CONSTRAINT primary_sensors PRIMARY KEY (id);


--
-- Name: primary_serverstats; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY serverstats
    ADD CONSTRAINT primary_serverstats PRIMARY KEY (id);


--
-- Name: primary_sessions; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY sessions
    ADD CONSTRAINT primary_sessions PRIMARY KEY (id);


--
-- Name: primary_severity; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY severity
    ADD CONSTRAINT primary_severity PRIMARY KEY (id);


--
-- Name: primary_stats_dialogue; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT primary_stats_dialogue PRIMARY KEY (id);


--
-- Name: primary_stats_history; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_history
    ADD CONSTRAINT primary_stats_history PRIMARY KEY (id);


--
-- Name: primary_stats_history_dialogue; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT primary_stats_history_dialogue PRIMARY KEY (historyid, dialogueid);


--
-- Name: primary_stats_history_virus; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT primary_stats_history_virus PRIMARY KEY (historyid, virusid);


--
-- Name: primary_stats_virus; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_virus
    ADD CONSTRAINT primary_stats_virus PRIMARY KEY (id);


--
-- Name: scanners_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);


--
-- Name: scheme_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY scheme
    ADD CONSTRAINT scheme_pkey PRIMARY KEY (version);


--
-- Name: sensor_details_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY sensor_details
    ADD CONSTRAINT sensor_details_pkey PRIMARY KEY (id);


--
-- Name: sensor_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY sensor_notes
    ADD CONSTRAINT sensor_notes_pkey PRIMARY KEY (id);


--
-- Name: sensorid; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos
    ADD CONSTRAINT sensorid UNIQUE (sensorid);


--
-- Name: sniff_hosttypes_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY sniff_hosttypes
    ADD CONSTRAINT sniff_hosttypes_pkey PRIMARY KEY (id);


--
-- Name: sniff_protos_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT sniff_protos_pkey PRIMARY KEY (id);


--
-- Name: syslog_events_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY syslog_events
    ADD CONSTRAINT syslog_events_pkey PRIMARY KEY (name);


--
-- Name: syslog_pkey; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY syslog
    ADD CONSTRAINT syslog_pkey PRIMARY KEY (id);


--
-- Name: system_ip_addr_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY system
    ADD CONSTRAINT system_ip_addr_key UNIQUE (ip_addr, name);


--
-- Name: system_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY system
    ADD CONSTRAINT system_pkey PRIMARY KEY (sid);


--
-- Name: uniq_binaries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT uniq_binaries_pkey PRIMARY KEY (id);


--
-- Name: unique_arp_static; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_static
    ADD CONSTRAINT unique_arp_static UNIQUE (mac, ip, sensorid);


--
-- Name: unique_arpcache_ip_sensorid; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT unique_arpcache_ip_sensorid UNIQUE (ip, sensorid);


--
-- Name: unique_bin; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT unique_bin UNIQUE (name);


--
-- Name: unique_identifier; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY org_id
    ADD CONSTRAINT unique_identifier UNIQUE (identifier);


--
-- Name: unique_imagename; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY argos_images
    ADD CONSTRAINT unique_imagename UNIQUE (imagename);


--
-- Name: unique_os; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY ostypes
    ADD CONSTRAINT unique_os UNIQUE (os);


--
-- Name: unique_pageconf; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY pageconf
    ADD CONSTRAINT unique_pageconf UNIQUE (userid, pageid);


--
-- Name: unique_severity; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY severity
    ADD CONSTRAINT unique_severity UNIQUE (val);


--
-- Name: unique_sniff_protos; Type: CONSTRAINT; Schema: public; Owner: surfnet; Tablespace: 
--

ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT unique_sniff_protos UNIQUE (sensorid, parent, number);


--
-- Name: unique_stats_dialogue; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT unique_stats_dialogue UNIQUE (name);


--
-- Name: first_tstamp_index; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX first_tstamp_index ON system USING btree (first_tstamp);


--
-- Name: index_attacks_dest; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_attacks_dest ON attacks USING btree (dest);


--
-- Name: index_attacks_id; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX index_attacks_id ON attacks USING btree (id);

ALTER TABLE attacks CLUSTER ON index_attacks_id;


--
-- Name: index_attacks_sensorid; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_attacks_sensorid ON attacks USING btree (sensorid);


--
-- Name: index_attacks_severity; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_attacks_severity ON attacks USING btree (severity);


--
-- Name: index_attacks_source; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_attacks_source ON attacks USING btree (source);


--
-- Name: index_attacks_timestamp; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_attacks_timestamp ON attacks USING btree ("timestamp");


--
-- Name: index_binaries; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_binaries ON binaries USING btree (bin);


--
-- Name: index_binaries_detail_bin; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX index_binaries_detail_bin ON binaries_detail USING btree (bin);


--
-- Name: index_binaries_detail_id; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX index_binaries_detail_id ON binaries_detail USING btree (id);

ALTER TABLE binaries_detail CLUSTER ON index_binaries_detail_id;


--
-- Name: index_binaries_info; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_binaries_info ON binaries USING btree (info);


--
-- Name: index_details_attackid; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_details_attackid ON details USING btree (attackid);


--
-- Name: index_details_id; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX index_details_id ON details USING btree (id);

ALTER TABLE details CLUSTER ON index_details_id;


--
-- Name: index_details_sensorid; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_details_sensorid ON details USING btree (sensorid);


--
-- Name: index_sensors_id; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX index_sensors_id ON sensors USING btree (id);

ALTER TABLE sensors CLUSTER ON index_sensors_id;


--
-- Name: index_sensors_organisation; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX index_sensors_organisation ON sensors USING btree (organisation);


--
-- Name: ip_addr_name_index; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX ip_addr_name_index ON system USING btree (ip_addr, name);


--
-- Name: last_tstamp_index; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX last_tstamp_index ON system USING btree (last_tstamp);


--
-- Name: sid_index; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX sid_index ON system_details USING btree (sid);


--
-- Name: system_details_ip_addr_index; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX system_details_ip_addr_index ON system_details USING btree (ip_addr);


--
-- Name: insert_name; Type: RULE; Schema: public; Owner: postgres
--

CREATE RULE insert_name AS ON INSERT TO system WHERE (NOT (split_part((new.name)::text, ' '::text, 1) IN (SELECT ostypes.os FROM ostypes))) DO INSERT INTO ostypes (os) VALUES (split_part((new.name)::text, ' '::text, 1));


--
-- Name: foreign_attack; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_attack FOREIGN KEY (attackid) REFERENCES attacks(id) ON DELETE CASCADE;


--
-- Name: foreign_indexmods; Type: FK CONSTRAINT; Schema: public; Owner: surfnet
--

ALTER TABLE ONLY indexmods_selected
    ADD CONSTRAINT foreign_indexmods FOREIGN KEY (login_id) REFERENCES login(id) ON DELETE CASCADE;


--
-- Name: foreign_pageconf; Type: FK CONSTRAINT; Schema: public; Owner: surfnet
--

ALTER TABLE ONLY pageconf
    ADD CONSTRAINT foreign_pageconf FOREIGN KEY (userid) REFERENCES login(id) ON DELETE CASCADE;


--
-- Name: foreign_report_content_login_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES login(id) ON DELETE CASCADE;


--
-- Name: foreign_sensor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY attacks
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);


--
-- Name: foreign_sensor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);


--
-- Name: foreign_stats_history; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stats_history
    ADD CONSTRAINT foreign_stats_history FOREIGN KEY (sensorid) REFERENCES sensors(id);


--
-- Name: foreign_stats_history_dialogue_foreignid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT foreign_stats_history_dialogue_foreignid FOREIGN KEY (dialogueid) REFERENCES stats_dialogue(id);


--
-- Name: foreign_stats_history_dialogue_historyid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT foreign_stats_history_dialogue_historyid FOREIGN KEY (historyid) REFERENCES stats_history(id);


--
-- Name: foreign_stats_history_virus_historyid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT foreign_stats_history_virus_historyid FOREIGN KEY (historyid) REFERENCES stats_history(id);


--
-- Name: foreign_stats_history_virus_virusid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT foreign_stats_history_virus_virusid FOREIGN KEY (virusid) REFERENCES stats_virus(id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- Name: argos; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE argos FROM PUBLIC;
REVOKE ALL ON TABLE argos FROM surfnet;
GRANT ALL ON TABLE argos TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos TO idslog;
GRANT SELECT ON TABLE argos TO argos;


--
-- Name: argos_images; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE argos_images FROM PUBLIC;
REVOKE ALL ON TABLE argos_images FROM surfnet;
GRANT ALL ON TABLE argos_images TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_images TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_images TO argos;


--
-- Name: argos_ranges; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE argos_ranges FROM PUBLIC;
REVOKE ALL ON TABLE argos_ranges FROM surfnet;
GRANT ALL ON TABLE argos_ranges TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_ranges TO idslog;


--
-- Name: argos_templates; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE argos_templates FROM PUBLIC;
REVOKE ALL ON TABLE argos_templates FROM surfnet;
GRANT ALL ON TABLE argos_templates TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE argos_templates TO idslog;


--
-- Name: arp_cache; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE arp_cache FROM PUBLIC;
REVOKE ALL ON TABLE arp_cache FROM surfnet;
GRANT ALL ON TABLE arp_cache TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_cache TO idslog;


--
-- Name: arp_excl; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE arp_excl FROM PUBLIC;
REVOKE ALL ON TABLE arp_excl FROM surfnet;
GRANT ALL ON TABLE arp_excl TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_excl TO idslog;


--
-- Name: arp_static; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE arp_static FROM PUBLIC;
REVOKE ALL ON TABLE arp_static FROM surfnet;
GRANT ALL ON TABLE arp_static TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE arp_static TO idslog;


--
-- Name: attacks; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE attacks FROM PUBLIC;
REVOKE ALL ON TABLE attacks FROM postgres;
GRANT ALL ON TABLE attacks TO postgres;
GRANT SELECT,INSERT,DELETE ON TABLE attacks TO idslog;
GRANT SELECT,INSERT,UPDATE ON TABLE attacks TO nepenthes;
GRANT SELECT,INSERT,UPDATE ON TABLE attacks TO argos;


--
-- Name: binaries; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE binaries FROM PUBLIC;
REVOKE ALL ON TABLE binaries FROM postgres;
GRANT ALL ON TABLE binaries TO postgres;
GRANT SELECT,INSERT,UPDATE ON TABLE binaries TO idslog;


--
-- Name: binaries_detail; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE binaries_detail FROM PUBLIC;
REVOKE ALL ON TABLE binaries_detail FROM postgres;
GRANT ALL ON TABLE binaries_detail TO postgres;
GRANT SELECT,INSERT,UPDATE ON TABLE binaries_detail TO idslog;


--
-- Name: cwsandbox; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE cwsandbox FROM PUBLIC;
REVOKE ALL ON TABLE cwsandbox FROM surfnet;
GRANT ALL ON TABLE cwsandbox TO surfnet;
GRANT SELECT,INSERT,UPDATE ON TABLE cwsandbox TO idslog;


--
-- Name: deactivated_attacks; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE deactivated_attacks FROM PUBLIC;
REVOKE ALL ON TABLE deactivated_attacks FROM surfnet;
GRANT ALL ON TABLE deactivated_attacks TO surfnet;
GRANT SELECT,INSERT,DELETE ON TABLE deactivated_attacks TO idslog;


--
-- Name: deactivated_details; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE deactivated_details FROM PUBLIC;
REVOKE ALL ON TABLE deactivated_details FROM surfnet;
GRANT ALL ON TABLE deactivated_details TO surfnet;
GRANT SELECT,INSERT,DELETE ON TABLE deactivated_details TO idslog;


--
-- Name: deactivated_sensors; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE deactivated_sensors FROM PUBLIC;
REVOKE ALL ON TABLE deactivated_sensors FROM surfnet;
GRANT ALL ON TABLE deactivated_sensors TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE deactivated_sensors TO idslog;


--
-- Name: details; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE details FROM PUBLIC;
REVOKE ALL ON TABLE details FROM postgres;
GRANT ALL ON TABLE details TO postgres;
GRANT SELECT,INSERT,DELETE ON TABLE details TO idslog;
GRANT SELECT,INSERT,UPDATE ON TABLE details TO nepenthes;
GRANT SELECT,INSERT,UPDATE ON TABLE details TO argos;


--
-- Name: groupmembers; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE groupmembers FROM PUBLIC;
REVOKE ALL ON TABLE groupmembers FROM surfnet;
GRANT ALL ON TABLE groupmembers TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE groupmembers TO idslog;


--
-- Name: groups; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE groups FROM PUBLIC;
REVOKE ALL ON TABLE groups FROM surfnet;
GRANT ALL ON TABLE groups TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE groups TO idslog;


--
-- Name: indexmods; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE indexmods FROM PUBLIC;
REVOKE ALL ON TABLE indexmods FROM surfnet;
GRANT ALL ON TABLE indexmods TO surfnet;
GRANT SELECT ON TABLE indexmods TO idslog;


--
-- Name: indexmods_selected; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE indexmods_selected FROM PUBLIC;
REVOKE ALL ON TABLE indexmods_selected FROM surfnet;
GRANT ALL ON TABLE indexmods_selected TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE indexmods_selected TO idslog;


--
-- Name: login; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE login FROM PUBLIC;
REVOKE ALL ON TABLE login FROM postgres;
GRANT ALL ON TABLE login TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE login TO idslog;


--
-- Name: logmessages; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE logmessages FROM PUBLIC;
REVOKE ALL ON TABLE logmessages FROM surfnet;
GRANT ALL ON TABLE logmessages TO surfnet;
GRANT SELECT ON TABLE logmessages TO idslog;


--
-- Name: norman; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE norman FROM PUBLIC;
REVOKE ALL ON TABLE norman FROM postgres;
GRANT ALL ON TABLE norman TO postgres;
GRANT SELECT,INSERT,UPDATE ON TABLE norman TO idslog;


--
-- Name: org_excl; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE org_excl FROM PUBLIC;
REVOKE ALL ON TABLE org_excl FROM surfnet;
GRANT ALL ON TABLE org_excl TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE org_excl TO idslog;


--
-- Name: org_id; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE org_id FROM PUBLIC;
REVOKE ALL ON TABLE org_id FROM postgres;
GRANT ALL ON TABLE org_id TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE org_id TO idslog;


--
-- Name: organisations; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE organisations FROM PUBLIC;
REVOKE ALL ON TABLE organisations FROM postgres;
GRANT ALL ON TABLE organisations TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE organisations TO idslog;


--
-- Name: ostypes; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE ostypes FROM PUBLIC;
REVOKE ALL ON TABLE ostypes FROM surfnet;
GRANT ALL ON TABLE ostypes TO surfnet;
GRANT SELECT,INSERT ON TABLE ostypes TO pofuser;
GRANT SELECT ON TABLE ostypes TO idslog;


--
-- Name: pageconf; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE pageconf FROM PUBLIC;
REVOKE ALL ON TABLE pageconf FROM surfnet;
GRANT ALL ON TABLE pageconf TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE pageconf TO idslog;


--
-- Name: report_content; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE report_content FROM PUBLIC;
REVOKE ALL ON TABLE report_content FROM postgres;
GRANT ALL ON TABLE report_content TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE report_content TO idslog;


--
-- Name: rrd; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE rrd FROM PUBLIC;
REVOKE ALL ON TABLE rrd FROM postgres;
GRANT ALL ON TABLE rrd TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE rrd TO idslog;


--
-- Name: scanners; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE scanners FROM PUBLIC;
REVOKE ALL ON TABLE scanners FROM postgres;
GRANT ALL ON TABLE scanners TO postgres;
GRANT SELECT,UPDATE ON TABLE scanners TO idslog;


--
-- Name: scheme; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE scheme FROM PUBLIC;
REVOKE ALL ON TABLE scheme FROM postgres;
GRANT ALL ON TABLE scheme TO postgres;
GRANT SELECT ON TABLE scheme TO pofuser;


--
-- Name: sensor_details; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE sensor_details FROM PUBLIC;
REVOKE ALL ON TABLE sensor_details FROM surfnet;
GRANT ALL ON TABLE sensor_details TO surfnet;
GRANT SELECT,INSERT,UPDATE ON TABLE sensor_details TO idslog;


--
-- Name: sensor_notes; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE sensor_notes FROM PUBLIC;
REVOKE ALL ON TABLE sensor_notes FROM surfnet;
GRANT ALL ON TABLE sensor_notes TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sensor_notes TO idslog;


--
-- Name: sensors; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE sensors FROM PUBLIC;
REVOKE ALL ON TABLE sensors FROM postgres;
GRANT ALL ON TABLE sensors TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sensors TO idslog;
GRANT SELECT ON TABLE sensors TO nepenthes;
GRANT SELECT ON TABLE sensors TO argos;


--
-- Name: serverstats; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE serverstats FROM PUBLIC;
REVOKE ALL ON TABLE serverstats FROM postgres;
GRANT ALL ON TABLE serverstats TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE serverstats TO idslog;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE serverstats TO argos;


--
-- Name: sessions; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE sessions FROM PUBLIC;
REVOKE ALL ON TABLE sessions FROM postgres;
GRANT ALL ON TABLE sessions TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sessions TO idslog;


--
-- Name: severity; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE severity FROM PUBLIC;
REVOKE ALL ON TABLE severity FROM postgres;
GRANT ALL ON TABLE severity TO postgres;
GRANT ALL ON TABLE severity TO idslog;


--
-- Name: sniff_hosttypes; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE sniff_hosttypes FROM PUBLIC;
REVOKE ALL ON TABLE sniff_hosttypes FROM surfnet;
GRANT ALL ON TABLE sniff_hosttypes TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sniff_hosttypes TO idslog;


--
-- Name: sniff_protos; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE sniff_protos FROM PUBLIC;
REVOKE ALL ON TABLE sniff_protos FROM surfnet;
GRANT ALL ON TABLE sniff_protos TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sniff_protos TO idslog;


--
-- Name: stats_dialogue; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE stats_dialogue FROM PUBLIC;
REVOKE ALL ON TABLE stats_dialogue FROM postgres;
GRANT ALL ON TABLE stats_dialogue TO postgres;
GRANT ALL ON TABLE stats_dialogue TO idslog;
GRANT SELECT,INSERT ON TABLE stats_dialogue TO nepenthes;


--
-- Name: stats_history; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE stats_history FROM PUBLIC;
REVOKE ALL ON TABLE stats_history FROM postgres;
GRANT ALL ON TABLE stats_history TO postgres;
GRANT ALL ON TABLE stats_history TO idslog;


--
-- Name: stats_history_dialogue; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE stats_history_dialogue FROM PUBLIC;
REVOKE ALL ON TABLE stats_history_dialogue FROM postgres;
GRANT ALL ON TABLE stats_history_dialogue TO postgres;
GRANT ALL ON TABLE stats_history_dialogue TO idslog;


--
-- Name: stats_history_virus; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE stats_history_virus FROM PUBLIC;
REVOKE ALL ON TABLE stats_history_virus FROM postgres;
GRANT ALL ON TABLE stats_history_virus TO postgres;
GRANT ALL ON TABLE stats_history_virus TO idslog;


--
-- Name: stats_virus; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE stats_virus FROM PUBLIC;
REVOKE ALL ON TABLE stats_virus FROM postgres;
GRANT ALL ON TABLE stats_virus TO postgres;
GRANT ALL ON TABLE stats_virus TO idslog;


--
-- Name: syslog; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON TABLE syslog FROM PUBLIC;
REVOKE ALL ON TABLE syslog FROM surfnet;
GRANT ALL ON TABLE syslog TO surfnet;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE syslog TO idslog;


--
-- Name: system; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE system FROM PUBLIC;
REVOKE ALL ON TABLE system FROM postgres;
GRANT ALL ON TABLE system TO postgres;
GRANT SELECT ON TABLE system TO idslog;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE system TO pofuser;


--
-- Name: system_details; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE system_details FROM PUBLIC;
REVOKE ALL ON TABLE system_details FROM postgres;
GRANT ALL ON TABLE system_details TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE system_details TO pofuser;


--
-- Name: uniq_binaries; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE uniq_binaries FROM PUBLIC;
REVOKE ALL ON TABLE uniq_binaries FROM postgres;
GRANT ALL ON TABLE uniq_binaries TO postgres;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE uniq_binaries TO idslog;
GRANT SELECT,INSERT ON TABLE uniq_binaries TO nepenthes;


--
-- Name: argos_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE argos_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE argos_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_id_seq TO argos;


--
-- Name: argos_images_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE argos_images_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE argos_images_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_images_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_images_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_images_id_seq TO argos;


--
-- Name: argos_ranges_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE argos_ranges_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE argos_ranges_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_ranges_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_ranges_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_ranges_id_seq TO argos;


--
-- Name: argos_templates_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE argos_templates_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE argos_templates_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_templates_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE argos_templates_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE argos_templates_id_seq TO argos;


--
-- Name: arp_cache_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE arp_cache_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE arp_cache_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_cache_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_cache_id_seq TO idslog;


--
-- Name: arp_excl_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE arp_excl_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE arp_excl_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_excl_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_excl_id_seq TO idslog;


--
-- Name: arp_static_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE arp_static_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE arp_static_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_static_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE arp_static_id_seq TO idslog;


--
-- Name: attacks_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE attacks_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE attacks_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON SEQUENCE attacks_id_seq TO argos;


--
-- Name: binaries_detail_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE binaries_detail_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE binaries_detail_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE binaries_detail_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE binaries_detail_id_seq TO idslog;


--
-- Name: binaries_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE binaries_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE binaries_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE binaries_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE binaries_id_seq TO idslog;


--
-- Name: deactivated_attacks_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE deactivated_attacks_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE deactivated_attacks_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_attacks_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_attacks_id_seq TO idslog;


--
-- Name: deactivated_details_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE deactivated_details_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE deactivated_details_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_details_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_details_id_seq TO idslog;


--
-- Name: deactivated_sensors_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE deactivated_sensors_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE deactivated_sensors_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_sensors_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE deactivated_sensors_id_seq TO idslog;


--
-- Name: details_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE details_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE details_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON SEQUENCE details_id_seq TO argos;


--
-- Name: groupmembers_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE groupmembers_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE groupmembers_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE groupmembers_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE groupmembers_id_seq TO idslog;


--
-- Name: groups_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE groups_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE groups_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE groups_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE groups_id_seq TO idslog;


--
-- Name: login_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE login_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE login_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE login_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE login_id_seq TO idslog;


--
-- Name: org_excl_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE org_excl_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE org_excl_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE org_excl_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE org_excl_id_seq TO idslog;


--
-- Name: org_id_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE org_id_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE org_id_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE org_id_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE org_id_id_seq TO idslog;


--
-- Name: organisations_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE organisations_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE organisations_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE organisations_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE organisations_id_seq TO idslog;


--
-- Name: ostypes_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE ostypes_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE ostypes_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE ostypes_id_seq TO surfnet;
GRANT SELECT ON SEQUENCE ostypes_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ostypes_id_seq TO pofuser;


--
-- Name: report_content_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE report_content_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE report_content_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE report_content_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE report_content_id_seq TO idslog;


--
-- Name: rrd_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE rrd_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE rrd_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE rrd_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE rrd_id_seq TO idslog;


--
-- Name: scanners_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE scanners_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE scanners_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE scanners_id_seq TO postgres;
GRANT SELECT ON SEQUENCE scanners_id_seq TO idslog;


--
-- Name: sensor_details_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE sensor_details_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sensor_details_id_seq FROM surfnet;
GRANT ALL ON SEQUENCE sensor_details_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sensor_details_id_seq TO idslog;


--
-- Name: sensor_notes_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE sensor_notes_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sensor_notes_id_seq FROM surfnet;
GRANT ALL ON SEQUENCE sensor_notes_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sensor_notes_id_seq TO idslog;


--
-- Name: sensors_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE sensors_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sensors_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE sensors_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE sensors_id_seq TO idslog;
GRANT SELECT ON SEQUENCE sensors_id_seq TO nepenthes;


--
-- Name: serverstats_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE serverstats_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE serverstats_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE serverstats_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE serverstats_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE serverstats_id_seq TO argos;


--
-- Name: sessions_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE sessions_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sessions_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE sessions_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE sessions_id_seq TO idslog;


--
-- Name: sniff_hosttypes_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE sniff_hosttypes_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sniff_hosttypes_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sniff_hosttypes_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sniff_hosttypes_id_seq TO idslog;


--
-- Name: sniff_protos_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE sniff_protos_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sniff_protos_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sniff_protos_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE sniff_protos_id_seq TO idslog;


--
-- Name: stats_dialogue_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE stats_dialogue_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE stats_dialogue_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_dialogue_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_dialogue_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE stats_dialogue_id_seq TO nepenthes;


--
-- Name: stats_history_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE stats_history_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE stats_history_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_history_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_history_id_seq TO idslog;


--
-- Name: stats_virus_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE stats_virus_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE stats_virus_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_virus_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE stats_virus_id_seq TO idslog;


--
-- Name: syslog_id_seq; Type: ACL; Schema: public; Owner: surfnet
--

REVOKE ALL ON SEQUENCE syslog_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE syslog_id_seq FROM surfnet;
GRANT SELECT,UPDATE ON SEQUENCE syslog_id_seq TO surfnet;
GRANT SELECT,UPDATE ON SEQUENCE syslog_id_seq TO idslog;


--
-- Name: system_sid_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE system_sid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE system_sid_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE system_sid_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE system_sid_seq TO pofuser;


--
-- Name: uniq_binaries_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE uniq_binaries_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE uniq_binaries_id_seq FROM postgres;
GRANT SELECT,UPDATE ON SEQUENCE uniq_binaries_id_seq TO postgres;
GRANT SELECT,UPDATE ON SEQUENCE uniq_binaries_id_seq TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE uniq_binaries_id_seq TO nepenthes;


--
-- PostgreSQL database dump complete
--

