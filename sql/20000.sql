-- SURFids 3.00
-- Database conversion 2.00.02 -> 2.00.03
-- Changeset 005
-- 25-09-2007

-- Changelog
-- 005 Added some modifications to the attacks table options
-- 004 Added atype update for the attacks table
-- 003 Fixed typos
-- 002 Changed report_content sql
-- 001 Initial release

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
-- ARGOS_TEMPLATES
--
SELECT pg_catalog.setval(pg_catalog.pg_get_serial_sequence('argos_templates', 'id'), 4, true);

INSERT INTO argos_templates VALUES (1, 'All Traffic', 'all');
INSERT INTO argos_templates VALUES (2, 'Top 100 of all your sensors', 'top100org');
INSERT INTO argos_templates VALUES (3, 'Top 100 of all sensors', 'top100all');
INSERT INTO argos_templates VALUES (4, 'Top 100 sensor', 'top100sensor');

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
ALTER TABLE attacks ADD COLUMN dst_mac macaddr;
ALTER TABLE attacks ADD COLUMN atype integer DEFAULT 0 NOT NULL;
ALTER TABLE attacks ALTER COLUMN sport SET DEFAULT 0;
ALTER TABLE attacks ALTER COLUMN dport SET DEFAULT 0;
ALTER TABLE attacks ALTER COLUMN dest DROP NOT NULL;

GRANT INSERT,SELECT,UPDATE ON TABLE attacks TO argos;
GRANT SELECT,UPDATE ON TABLE attacks_id_seq TO argos;

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
-- LOGIN
--
ALTER TABLE login DROP COLUMN maillog;

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

INSERT INTO logmessages VALUES (1, 30, 'Sensor starting up!');
INSERT INTO logmessages VALUES (3, 30, 'Sensor stopped!');
INSERT INTO logmessages VALUES (4, 30, 'Disabled SSH!');
INSERT INTO logmessages VALUES (5, 30, 'Enabled SSH!');
INSERT INTO logmessages VALUES (6, 30, 'Enabled sensor!');
INSERT INTO logmessages VALUES (7, 30, 'Disabled sensor!');
INSERT INTO logmessages VALUES (8, 30, 'Rebooting sensor!');
INSERT INTO logmessages VALUES (11, 30, 'Sensor started!');
INSERT INTO logmessages VALUES (12, 30, 'Network configuration changed!');
INSERT INTO logmessages VALUES (2, 40, 'Warning: No static IP configuration on the server!');
INSERT INTO logmessages VALUES (9, 50, 'Error: No tap device present!');
INSERT INTO logmessages VALUES (10, 50, 'Error: Tap device could not get an IP address!');
INSERT INTO logmessages VALUES (13, 20, 'Sensor local IP address changed!');
INSERT INTO logmessages VALUES (14, 20, 'Sensor remote IP address changed!');

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
-- REPORT_CONTENT
--
ALTER TABLE report_content DROP COLUMN title;
ALTER TABLE report_content ALTER COLUMN active SET DEFAULT true;
ALTER TABLE report_content ALTER COLUMN active SET NOT NULL;
ALTER TABLE report_content ALTER COLUMN interval SET DEFAULT -1;
ALTER TABLE report_content ALTER COLUMN interval SET NOT NULL;

ALTER TABLE report_content ADD COLUMN "operator" integer DEFAULT -1 NOT NULL;
ALTER TABLE report_content ADD COLUMN threshold integer DEFAULT -1 NOT NULL;
ALTER TABLE report_content ADD COLUMN severity integer DEFAULT -1 NOT NULL;
ALTER TABLE report_content ADD COLUMN detail integer DEFAULT 0 NOT NULL;
ALTER TABLE report_content ADD COLUMN qs character varying;
ALTER TABLE report_content ADD COLUMN from_ts integer DEFAULT -1 NOT NULL;
ALTER TABLE report_content ADD COLUMN to_ts integer DEFAULT -1 NOT NULL;

UPDATE report_content SET severity = report_template_threshold.target 
FROM report_template_threshold 
WHERE report_content.id = report_template_threshold.report_content_id;

UPDATE report_content SET threshold = report_template_threshold.value 
FROM report_template_threshold 
WHERE report_content.id = report_template_threshold.report_content_id;

UPDATE report_content SET threshold = 1, subject = report_content.subject || ' NEEDS CONFIGURATION' 
FROM report_template_threshold 
WHERE report_content.id = report_template_threshold.report_content_id AND report_template_threshold.value = -1;

UPDATE report_content SET operator = report_template_threshold.operator
FROM report_template_threshold
WHERE report_content.id = report_template_threshold.report_content_id;

--
-- REPORT_TEMPLATE_THRESHOLD
--
DROP TABLE report_template_threshold;

--
-- SCANNERS
--
ALTER TABLE scanners ADD COLUMN vercommand character varying;
ALTER TABLE scanners ADD COLUMN version character varying;

--
-- SENSORS
--
ALTER TABLE sensors ADD COLUMN arp integer DEFAULT 0 NOT NULL;
ALTER TABLE sensors ADD COLUMN label character varying;

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
