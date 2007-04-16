--
-- SURFnet IDS database structure
-- Version 1.04.12
-- 16-04-2007
--

-- Version history
-- 1.04.12 Added privileges for nepenthes user on stats_dialogue and uniq_binaries
-- 1.04.11 Added default value for status
-- 1.04.10 Removed nepenthes functions, located in separate sql file now
-- 1.04.09 Updated p0f-db tables
-- 1.04.08 Removed idslog priviliges for scanners
-- 1.04.07 Removed arp stuff from sensors table
-- 1.04.06 Updating sql file for 1.04beta
-- 1.04.05 Changed constraint for report_content
-- 1.04.04 Removed table report and modified login
-- 1.04.03 Switched source and dest in the Nepenthes function surfnet_attack_by_id
-- 1.04.02 Added Nepenthes log-surfnet pgsql functions
-- 1.04.01 Removed the tbl_ references
-- 1.02.06 Added serverhash column to the login table
-- 1.02.05 Initial 1.03 release

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
    vlanid integer DEFAULT 0
);

ALTER TABLE ONLY sensors
    ADD CONSTRAINT primary_sensors PRIMARY KEY (id);

CREATE UNIQUE INDEX index_sensors_id ON sensors USING btree (id);
ALTER TABLE sensors CLUSTER ON index_sensors_id;
CREATE INDEX index_sensors_organisation ON sensors USING btree (organisation);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sensors TO idslog;
GRANT SELECT ON TABLE sensors TO nepenthes;
GRANT ALL ON TABLE sensors_id_seq TO idslog;
GRANT INSERT,SELECT,RULE,DELETE,REFERENCES,TRIGGER ON TABLE sensors_id_seq TO nepenthes;

--
-- ATTACKS
--
CREATE TABLE attacks (
    id serial NOT NULL,
    "timestamp" integer NOT NULL,
    severity integer NOT NULL,
    source inet NOT NULL,
    sport integer NOT NULL,
    dest inet NOT NULL,
    dport integer NOT NULL,
    sensorid integer NOT NULL,
    src_mac macaddr
);

ALTER TABLE ONLY attacks
    ADD CONSTRAINT primary_attacks PRIMARY KEY (id);

CREATE INDEX index_attacks_dest ON attacks USING btree (dest);
CREATE UNIQUE INDEX index_attacks_id ON attacks USING btree (id);
ALTER TABLE attacks CLUSTER ON index_attacks_id;
CREATE INDEX index_attacks_sensorid ON attacks USING btree (sensorid);
CREATE INDEX index_attacks_severity ON attacks USING btree (severity);
CREATE INDEX index_attacks_source ON attacks USING btree (source);
CREATE INDEX index_attacks_timestamp ON attacks USING btree ("timestamp");
ALTER TABLE ONLY attacks
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

GRANT SELECT,DELETE ON TABLE attacks TO idslog;
GRANT INSERT,SELECT,UPDATE ON TABLE attacks TO nepenthes;

GRANT ALL ON TABLE attacks_id_seq TO idslog;
GRANT ALL ON TABLE attacks_id_seq TO nepenthes;

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

CREATE INDEX index_details_attackid ON details USING btree (attackid);
CREATE UNIQUE INDEX index_details_id ON details USING btree (id);
ALTER TABLE details CLUSTER ON index_details_id;
CREATE INDEX index_details_sensorid ON details USING btree (sensorid);
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_attack FOREIGN KEY (attackid) REFERENCES attacks(id);
ALTER TABLE ONLY details
    ADD CONSTRAINT foreign_sensor FOREIGN KEY (sensorid) REFERENCES sensors(id);

GRANT SELECT ON TABLE details TO idslog;
GRANT INSERT,SELECT,UPDATE ON TABLE details TO nepenthes;
GRANT ALL ON TABLE details_id_seq TO idslog;
GRANT ALL ON TABLE details_id_seq TO nepenthes;

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
    title character varying,
    "template" integer,
    last_sent integer,
    active boolean,
    sensor_id integer,
    frequency integer,
    "interval" integer,
    priority integer,
    subject character varying
);

ALTER TABLE ONLY report_content
    ADD CONSTRAINT primary_report_content PRIMARY KEY (id);
ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES "login"(id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE report_content TO idslog;
GRANT SELECT,UPDATE ON TABLE report_content_id_seq TO idslog;

--
-- REPORT_TEMPLATE_THRESHOLD
--
CREATE TABLE report_template_threshold (
    id serial NOT NULL,
    report_content_id integer NOT NULL,
    target integer,
    value integer,
    deviation integer,
    "operator" integer
);

ALTER TABLE ONLY report_template_threshold
    ADD CONSTRAINT primary_report_template_threshold PRIMARY KEY (id);
ALTER TABLE ONLY report_template_threshold
    ADD CONSTRAINT foreign_report_template_threshold_report_content_id FOREIGN KEY (report_content_id) REFERENCES report_content(id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE report_template_threshold TO idslog;
GRANT SELECT,UPDATE ON TABLE report_template_threshold_id_seq TO idslog;

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
    status integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE scanners TO idslog;
GRANT SELECT ON TABLE scanners_id_seq TO idslog;

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
GRANT SELECT,UPDATE ON TABLE serverstats_id_seq TO idslog;

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

GRANT ALL ON TABLE stats_dialogue TO idslog;
GRANT SELECT,UPDATE ON TABLE stats_dialogue_id_seq TO idslog;
GRANT INSERT,SELECT ON TABLE stats_dialogue TO nepenthes;
GRANT SELECT,UPDATE ON TABLE stats_dialogue_id_seq TO nepenthes;

--
-- STATS_HISTORY
--
CREATE TABLE stats_history (
    id serial NOT NULL,
    sensorid integer NOT NULL,
    "month" integer NOT NULL,
    "year" integer NOT NULL,
    count_possible integer DEFAULT 0,
    count_malicious integer DEFAULT 0,
    count_offered integer DEFAULT 0,
    count_downloaded integer DEFAULT 0,
    "timestamp" integer,
    uptime integer
);

ALTER TABLE ONLY stats_history
    ADD CONSTRAINT primary_stats_history PRIMARY KEY (id);
ALTER TABLE ONLY stats_history
    ADD CONSTRAINT foreign_stats_history FOREIGN KEY (sensorid) REFERENCES sensors(id);

GRANT ALL ON TABLE stats_history TO idslog;
GRANT SELECT,UPDATE ON TABLE stats_history_id_seq TO idslog;

--
-- STATS_HISTORY_DIALOGUE
--
CREATE TABLE stats_history_dialogue (
    historyid integer NOT NULL,
    dialogueid integer NOT NULL,
    count integer DEFAULT 1
);

ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT primary_stats_history_dialogue PRIMARY KEY (historyid, dialogueid);
ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT foreign_stats_history_dialogue_foreignid FOREIGN KEY (dialogueid) REFERENCES stats_dialogue(id);
ALTER TABLE ONLY stats_history_dialogue
    ADD CONSTRAINT foreign_stats_history_dialogue_historyid FOREIGN KEY (historyid) REFERENCES stats_history(id);

GRANT ALL ON TABLE stats_history_dialogue TO idslog;

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
-- STATS_HISTORY_VIRUS
--
CREATE TABLE stats_history_virus (
    historyid integer NOT NULL,
    virusid integer NOT NULL,
    count integer DEFAULT 1
);

ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT primary_stats_history_virus PRIMARY KEY (historyid, virusid);
ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT foreign_stats_history_virus_historyid FOREIGN KEY (historyid) REFERENCES stats_history(id);
ALTER TABLE ONLY stats_history_virus
    ADD CONSTRAINT foreign_stats_history_virus_virusid FOREIGN KEY (virusid) REFERENCES stats_virus(id);

GRANT ALL ON TABLE stats_history_virus TO idslog;

--
-- SCHEME
--
CREATE TABLE scheme (
    version     integer PRIMARY KEY,
    created     timestamp with time zone NOT NULL
);

INSERT INTO scheme (version, created) VALUES (1002, CURRENT_TIMESTAMP);

--
-- SYSTEM
--
CREATE TABLE system (
        sid             bigserial PRIMARY KEY,
        ip_addr         inet NOT NULL,
        name            character varying(128) NOT NULL,
        first_tstamp    timestamp with time zone,
        last_tstamp     timestamp with time zone NOT NULL,
        UNIQUE(ip_addr, name)
);

CREATE INDEX ip_addr_name_index ON system (ip_addr, name);
CREATE INDEX first_tstamp_index ON system (first_tstamp);
CREATE INDEX last_tstamp_index ON system (last_tstamp);

ALTER TABLE ONLY "system"
    ADD CONSTRAINT system_pkey PRIMARY KEY (ip_addr, name);

GRANT SELECT ON TABLE "system" TO idslog;
GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE "system" TO pofuser;
GRANT SELECT,UPDATE ON TABLE system_sid_seq TO pofuser;

--
-- SYSTEM_DETAILS
--
CREATE TABLE system_details (
        sid integer NOT NULL,
        ip_addr inet NOT NULL,
        nat character varying(64) NOT NULL DEFAULT 'no/unknown',
        ecn character varying(64) NOT NULL DEFAULT 'no/unknown',
        firewall character varying(64) NOT NULL DEFAULT 'no/unknown',
        lookup_link character varying(128) NOT NULL DEFAULT 'unknown',
        distance smallint NOT NULL DEFAULT 0
);

CREATE INDEX sid_index ON system_details (sid);
CREATE INDEX system_details_ip_addr_index ON system_details (ip_addr);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE "system_details" TO pofuser;

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
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO idslog;
GRANT INSERT,SELECT ON TABLE uniq_binaries TO nepenthes;
GRANT SELECT,UPDATE ON TABLE uniq_binaries_id_seq TO nepenthes;
