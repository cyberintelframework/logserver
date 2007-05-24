-- SURFnet IDS SQL Version 1.041
-- Version: 1.04.01
-- 09-05-2007

-- Changelog
-- 1.04.01 Initial release

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
-- ARP_ALERT
--
CREATE TABLE arp_alert (
    id serial NOT NULL,
    sensorid integer NOT NULL,
    "timestamp" integer NOT NULL,
    targetmac macaddr NOT NULL,
    sourcemac macaddr NOT NULL,
    targetip inet NOT NULL,
    "type" integer NOT NULL
);

ALTER TABLE ONLY arp_alert
    ADD CONSTRAINT arp_alert_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE arp_alert TO idslog;
GRANT SELECT,UPDATE ON TABLE arp_alert_id_seq TO idslog;

--
-- ARP_CACHE
--
CREATE TABLE arp_cache (
    id serial NOT NULL,
    mac macaddr NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL,
    last_seen integer NOT NULL,
    manufacturer character varying
);

ALTER TABLE ONLY arp_cache
    ADD CONSTRAINT arp_cache_pkey PRIMARY KEY (id);

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

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE arp_static TO idslog;
GRANT SELECT,UPDATE ON TABLE arp_static_id_seq TO idslog;

