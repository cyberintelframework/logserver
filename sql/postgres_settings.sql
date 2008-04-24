--
-- SURFids 2.10.00
-- Database structure
-- Changeset 002
-- 18-04-2008
--

--
-- Version history
-- 002 Added arp_excl
-- 001 Initial release
--

--
-- SENSORS 
--

CREATE TABLE sensors (
    id serial NOT NULL,
    keyname character varying NOT NULL,
    remoteip inet NOT NULL,
    localip inet NOT NULL,
    lastupdate integer,
    laststart integer,
    "action" character varying,
    ssh integer DEFAULT 1,
    status integer DEFAULT 0,
    uptime integer,
    laststop integer,
    tap character varying,
    tapip inet,
    mac macaddr,
    netconf text,
    organisation integer DEFAULT 0 NOT NULL,
    server integer DEFAULT 1 NOT NULL,
    netconfdetail text,
    vlanid integer DEFAULT 0,
    arp integer DEFAULT 0 NOT NULL,
    label character varying
);

ALTER TABLE ONLY sensors
    ADD CONSTRAINT primary_sensors PRIMARY KEY (id);

CREATE UNIQUE INDEX index_sensors_id ON sensors USING btree (id);
CREATE INDEX index_sensors_organisation ON sensors USING btree (organisation);

ALTER TABLE sensors CLUSTER ON index_sensors_id;

GRANT INSERT,SELECT,UPDATE ON TABLE sensors TO idslog;
GRANT SELECT ON TABLE sensors TO nepenthes;
GRANT SELECT ON TABLE sensors TO argos;

GRANT ALL ON TABLE sensors_id_seq TO idslog;
GRANT ALL ON TABLE sensors_id_seq TO nepenthes;

--
-- ARGOS 
--

CREATE TABLE argos (
    id serial NOT NULL,
    sensorid integer,
    imageid integer,
    templateid integer,
    timespan character varying
);

ALTER TABLE ONLY argos
    ADD CONSTRAINT primary_argos PRIMARY KEY (id);
ALTER TABLE ONLY argos
    ADD CONSTRAINT sensorid UNIQUE (sensorid);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE argos TO idslog;
GRANT SELECT ON TABLE argos TO argos;

GRANT SELECT,UPDATE ON TABLE argos_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_id_seq TO argos;

--
-- ARGOS_IMAGES 
--

CREATE TABLE argos_images (
    id serial NOT NULL,
    name character varying,
    serverip inet,
    macaddr macaddr,
    imagename character varying,
    osname character varying,
    oslang character varying,
    organisationid integer DEFAULT 0
);

ALTER TABLE ONLY argos_images
    ADD CONSTRAINT primary_argos_images PRIMARY KEY (id);

ALTER TABLE ONLY argos_images
    ADD CONSTRAINT unique_imagename UNIQUE (imagename);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE argos_images TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_images TO argos;

GRANT SELECT,UPDATE ON TABLE argos_images_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_images_id_seq TO argos;

--
-- ARGOS_RANGES 
--

CREATE TABLE argos_ranges (
    id serial NOT NULL,
    sensorid integer,
    range inet
);

ALTER TABLE ONLY argos_ranges
    ADD CONSTRAINT argos_ranges_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE argos_ranges TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_ranges_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_ranges_id_seq TO argos;

--
-- ARGOS_TEMPLATES 
--

CREATE TABLE argos_templates (
    id serial NOT NULL,
    name character varying,
    abbr character varying
);

ALTER TABLE ONLY argos_templates
    ADD CONSTRAINT argos_templates_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE argos_templates TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_templates_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE argos_templates_id_seq TO argos;

--
-- ARP_CACHE 
--

CREATE TABLE arp_cache (
    id serial NOT NULL,
    mac macaddr NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL,
    last_seen integer NOT NULL,
    manufacturer character varying,
    flags character varying
);

ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT arp_cache_pkey PRIMARY KEY (id);

ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT unique_arpcache_ip_sensorid UNIQUE (ip, sensorid);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE arp_cache TO idslog;
GRANT SELECT,UPDATE ON TABLE arp_cache_id_seq TO idslog;

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
-- ARP_STATIC 
--

CREATE TABLE arp_static (
    id serial NOT NULL,
    mac macaddr NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

ALTER TABLE ONLY arp_static
    ADD CONSTRAINT arp_static_pkey PRIMARY KEY (id);

ALTER TABLE ONLY arp_static
    ADD CONSTRAINT unique_arp_static UNIQUE (mac, ip, sensorid);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE arp_static TO idslog;
GRANT SELECT,UPDATE ON TABLE arp_static_id_seq TO idslog;

--
-- ATTACKS 
--

CREATE TABLE attacks (
    id serial NOT NULL,
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

ALTER TABLE ONLY attacks
    ADD CONSTRAINT primary_attacks PRIMARY KEY (id);

ALTER TABLE ONLY attacks
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

CREATE INDEX index_attacks_dest ON attacks USING btree (dest);
CREATE UNIQUE INDEX index_attacks_id ON attacks USING btree (id);
CREATE INDEX index_attacks_sensorid ON attacks USING btree (sensorid);
CREATE INDEX index_attacks_severity ON attacks USING btree (severity);
CREATE INDEX index_attacks_source ON attacks USING btree (source);
CREATE INDEX index_attacks_timestamp ON attacks USING btree ("timestamp");

ALTER TABLE attacks CLUSTER ON index_attacks_id;

GRANT INSERT,SELECT ON TABLE attacks TO idslog;
GRANT INSERT,SELECT,UPDATE ON TABLE attacks TO nepenthes;
GRANT INSERT,SELECT,UPDATE ON TABLE attacks TO argos;

GRANT ALL ON TABLE attacks_id_seq TO idslog;
GRANT ALL ON TABLE attacks_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON TABLE attacks_id_seq TO argos;

--
-- BINARIES 
--

CREATE TABLE binaries (
    id serial NOT NULL,
    "timestamp" integer,
    bin integer,
    info integer,
    scanner integer
);

ALTER TABLE ONLY binaries
    ADD CONSTRAINT primary_binaries PRIMARY KEY (id);

CREATE INDEX index_binaries ON binaries USING btree (bin);
CREATE INDEX index_binaries_info ON binaries USING btree (info);

GRANT INSERT,SELECT,UPDATE ON TABLE binaries TO idslog;

GRANT SELECT,UPDATE ON TABLE binaries_id_seq TO idslog;

--
-- BINARIES_DETAIL 
--

CREATE TABLE binaries_detail (
    id serial NOT NULL,
    bin integer,
    fileinfo character varying,
    filesize integer
);

CREATE UNIQUE INDEX index_binaries_detail_bin ON binaries_detail USING btree (bin);
CREATE UNIQUE INDEX index_binaries_detail_id ON binaries_detail USING btree (id);

ALTER TABLE binaries_detail CLUSTER ON index_binaries_detail_id;

GRANT INSERT,SELECT ON TABLE binaries_detail TO idslog;
GRANT SELECT,UPDATE ON TABLE binaries_detail_id_seq TO idslog;

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

GRANT INSERT,SELECT,UPDATE ON TABLE cwsandbox TO idslog;

--
-- DETAILS 
--

CREATE TABLE details (
    id serial NOT NULL,
    attackid integer NOT NULL,
    sensorid integer NOT NULL,
    "type" integer NOT NULL,
    text text NOT NULL
);

ALTER TABLE ONLY details
    ADD CONSTRAINT primary_details PRIMARY KEY (id);
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_attack FOREIGN KEY (attackid) REFERENCES attacks(id);
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

CREATE INDEX index_details_attackid ON details USING btree (attackid);
CREATE UNIQUE INDEX index_details_id ON details USING btree (id);
CREATE INDEX index_details_sensorid ON details USING btree (sensorid);

ALTER TABLE details CLUSTER ON index_details_id;

GRANT SELECT ON TABLE details TO idslog;
GRANT INSERT,SELECT,UPDATE ON TABLE details TO nepenthes;
GRANT INSERT,SELECT,UPDATE ON TABLE details TO argos;

GRANT ALL ON TABLE details_id_seq TO idslog;
GRANT ALL ON TABLE details_id_seq TO nepenthes;
GRANT SELECT,UPDATE ON TABLE details_id_seq TO argos;

--
-- LOGIN 
--

CREATE TABLE "login" (
    id serial NOT NULL,
    username character varying NOT NULL,
    "password" character varying NOT NULL,
    email character varying,
    maillog integer DEFAULT 0,
    lastlogin integer,
    organisation integer DEFAULT 0 NOT NULL,
    "access" character varying DEFAULT '000'::character varying NOT NULL,
    serverhash character varying,
    gpg integer DEFAULT 0
);

ALTER TABLE ONLY "login"
    ADD CONSTRAINT primary_login PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE "login" TO idslog;
GRANT ALL ON TABLE login_id_seq TO idslog;

--
-- LOGMESSAGES 
--

CREATE TABLE logmessages (
    id integer NOT NULL,
    "type" integer NOT NULL,
    log character varying NOT NULL
);

ALTER TABLE ONLY logmessages
    ADD CONSTRAINT logmessages_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE logmessages TO idslog;

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
-- ORG_EXCL 
--

CREATE TABLE org_excl (
    id serial NOT NULL,
    orgid integer NOT NULL,
    exclusion inet NOT NULL
);

ALTER TABLE ONLY org_excl
    ADD CONSTRAINT org_excl_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE org_excl TO idslog;
GRANT SELECT,UPDATE ON TABLE org_excl_id_seq TO idslog;

--
-- ORG_ID 
--

CREATE TABLE org_id (
    id serial NOT NULL,
    orgid integer NOT NULL,
    identifier character varying NOT NULL,
    "type" integer
);

ALTER TABLE ONLY org_id
    ADD CONSTRAINT primary_org_id_id PRIMARY KEY (id);

ALTER TABLE ONLY org_id
    ADD CONSTRAINT unique_identifier UNIQUE (identifier);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE org_id TO idslog;
GRANT SELECT,UPDATE ON TABLE org_id_id_seq TO idslog;

--
-- ORGANISATIONS 
--

CREATE TABLE organisations (
    id serial NOT NULL,
    organisation character varying NOT NULL,
    ranges text
);

ALTER TABLE ONLY organisations
    ADD CONSTRAINT primary_organisations PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE organisations TO idslog;
GRANT SELECT,UPDATE ON TABLE organisations_id_seq TO idslog;

--
-- REPORT_CONTENT 
--

CREATE TABLE report_content (
    id serial NOT NULL,
    user_id integer,
    "template" integer,
    last_sent integer,
    active boolean DEFAULT true NOT NULL,
    sensor_id integer,
    frequency integer,
    "interval" integer DEFAULT -1 NOT NULL,
    priority integer,
    subject character varying,
    "operator" integer DEFAULT -1 NOT NULL,
    threshold integer DEFAULT -1 NOT NULL,
    severity integer DEFAULT -1 NOT NULL,
    detail integer DEFAULT 0 NOT NULL,
    qs character varying,
    from_ts integer DEFAULT -1 NOT NULL,
    to_ts integer DEFAULT -1 NOT NULL
);

ALTER TABLE ONLY report_content
    ADD CONSTRAINT primary_report_content PRIMARY KEY (id);
ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES "login"(id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE report_content TO idslog;
GRANT SELECT,UPDATE ON TABLE report_content_id_seq TO idslog;

--
-- RRD 
--

CREATE TABLE rrd (
    id serial NOT NULL,
    orgid integer NOT NULL,
    "type" character varying NOT NULL,
    label character varying NOT NULL,
    image character varying NOT NULL,
    "timestamp" integer
);

ALTER TABLE ONLY rrd
    ADD CONSTRAINT primary_rrd PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE rrd TO idslog;
GRANT SELECT,UPDATE ON TABLE rrd_id_seq TO idslog;

--
-- SCANNERS 
--

CREATE TABLE scanners (
    id serial NOT NULL,
    name character varying,
    command character varying,
    "update" character varying,
    status integer DEFAULT 0 NOT NULL,
    vercommand character varying,
    version character varying
);

ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);

GRANT SELECT,UPDATE ON TABLE scanners TO idslog;
GRANT SELECT ON TABLE scanners_id_seq TO idslog;

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

--
-- SENSORS_LOG 
--

CREATE TABLE sensors_log (
    id serial NOT NULL,
    sensorid integer NOT NULL,
    "timestamp" integer NOT NULL,
    logid integer NOT NULL
);

ALTER TABLE ONLY sensors_log
    ADD CONSTRAINT sensors_log_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sensors_log TO idslog;
GRANT SELECT,UPDATE ON TABLE sensors_log_id_seq TO idslog;

--
-- SERVERS 
--

CREATE TABLE servers (
    id serial NOT NULL,
    server character varying NOT NULL
);

ALTER TABLE ONLY servers
    ADD CONSTRAINT primary_servers PRIMARY KEY (id);

GRANT INSERT,SELECT,DELETE ON TABLE servers TO idslog;
GRANT SELECT,UPDATE ON TABLE servers_id_seq TO idslog;

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
GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE serverstats TO argos;

GRANT SELECT,UPDATE ON TABLE serverstats_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE serverstats_id_seq TO argos;

--
-- SESSIONS 
--

CREATE TABLE sessions (
    id serial NOT NULL,
    sid character varying NOT NULL,
    ip inet NOT NULL,
    ts integer NOT NULL,
    username integer,
    useragent character varying
);

ALTER TABLE ONLY sessions
    ADD CONSTRAINT primary_sessions PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sessions TO idslog;
GRANT SELECT,UPDATE ON TABLE sessions_id_seq TO idslog;

--
-- SEVERITY 
--

CREATE TABLE severity (
    id integer NOT NULL,
    val character(2) NOT NULL,
    txt character varying NOT NULL
);

ALTER TABLE ONLY severity
    ADD CONSTRAINT primary_severity PRIMARY KEY (id);
ALTER TABLE ONLY severity
    ADD CONSTRAINT unique_severity UNIQUE (val);

GRANT ALL ON TABLE severity TO idslog;

--
-- SNIFF_HOSTTYPES 
--

CREATE TABLE sniff_hosttypes (
    id serial NOT NULL,
    staticid integer NOT NULL,
    "type" integer NOT NULL
);

ALTER TABLE ONLY sniff_hosttypes
    ADD CONSTRAINT sniff_hosttypes_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sniff_hosttypes TO idslog;
GRANT SELECT,UPDATE ON TABLE sniff_hosttypes_id_seq TO idslog;

--
-- SNIFF_PROTOS 
--

CREATE TABLE sniff_protos (
    id serial NOT NULL,
    sensorid integer NOT NULL,
    parent integer NOT NULL,
    number integer NOT NULL,
    protocol character varying NOT NULL
);

ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT sniff_protos_pkey PRIMARY KEY (id);
ALTER TABLE ONLY sniff_protos
    ADD CONSTRAINT unique_sniff_protos UNIQUE (sensorid, parent, number);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sniff_protos TO idslog;
GRANT SELECT,UPDATE ON TABLE sniff_protos_id_seq TO idslog;

--
-- STATS_DIALOGUE 
--

CREATE TABLE stats_dialogue (
    id serial NOT NULL,
    "desc" character varying,
    url character varying,
    name character varying
);

ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT primary_stats_dialogue PRIMARY KEY (id);
ALTER TABLE ONLY stats_dialogue
    ADD CONSTRAINT unique_stats_dialogue UNIQUE (name);

GRANT ALL ON TABLE stats_dialogue TO idslog;
GRANT INSERT,SELECT ON TABLE stats_dialogue TO nepenthes;

GRANT SELECT,UPDATE ON TABLE stats_dialogue_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE stats_dialogue_id_seq TO nepenthes;

--
-- STATS_VIRUS 
--

CREATE TABLE stats_virus (
    id serial NOT NULL,
    name character varying
);

ALTER TABLE ONLY stats_virus
    ADD CONSTRAINT primary_stats_virus PRIMARY KEY (id);

GRANT ALL ON TABLE stats_virus TO idslog;
GRANT SELECT,UPDATE ON TABLE stats_virus_id_seq TO idslog;

--
-- SYSTEM 
--

CREATE TABLE "system" (
    sid bigserial NOT NULL,
    ip_addr inet NOT NULL,
    name character varying(128) NOT NULL,
    first_tstamp timestamp with time zone,
    last_tstamp timestamp with time zone NOT NULL
);

ALTER TABLE ONLY "system"
    ADD CONSTRAINT system_ip_addr_key UNIQUE (ip_addr, name);
ALTER TABLE ONLY "system"
    ADD CONSTRAINT system_pkey PRIMARY KEY (sid);

CREATE INDEX first_tstamp_index ON "system" USING btree (first_tstamp);
CREATE INDEX last_tstamp_index ON "system" USING btree (last_tstamp);
CREATE INDEX ip_addr_name_index ON "system" USING btree (ip_addr, name);

GRANT SELECT ON TABLE "system" TO idslog;
GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE "system" TO pofuser;

GRANT SELECT,UPDATE ON TABLE system_sid_seq TO pofuser;

--
-- SYSTEM_DETAILS 
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

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE system_details TO pofuser;

--
-- UNIQ_BINARIES 
--

CREATE TABLE uniq_binaries (
    id serial NOT NULL,
    name character varying
);

ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT uniq_binaries_pkey PRIMARY KEY (id);
ALTER TABLE ONLY uniq_binaries
    ADD CONSTRAINT unique_bin UNIQUE (name);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE uniq_binaries TO idslog;
GRANT INSERT,SELECT ON TABLE uniq_binaries TO nepenthes;

GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO nepenthes;
