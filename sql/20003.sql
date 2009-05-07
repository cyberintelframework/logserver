-- SURFids 3.00
-- Database conversion 2.00.02 -> 2.00.03
-- Changeset 006
-- 06-06-2008
--

--
-- Changelog
-- 006 Removed arp_excl and login changes
-- 005 Added sensors changes
-- 004 Added logmessages change
-- 003 Added report_content modification
-- 002 Added LOGIN modifications
-- 001 Initial release
--

--
-- LOGMESSAGES
--
INSERT INTO logmessages VALUES (17, 30, 'Sensor updated to new revision: %1!');

--
-- REPORT_CONTENT
--
ALTER TABLE report_content ADD COLUMN always integer DEFAULT 0 NOT NULL;
ALTER TABLE report_content ADD COLUMN utc integer DEFAULT 0 NOT NULL;

--
-- SENSORS
--
ALTER TABLE sensors ADD COLUMN rev integer DEFAULT 0 NOT NULL;
ALTER TABLE sensors ADD COLUMN version character varying;
ALTER TABLE sensors ADD COLUMN sensormac macaddr;

--
-- SENSORS_LOG
--
ALTER TABLE sensors ADD COLUMN args character varying;

ALTER TABLE ONLY sensors_log
    ADD CONSTRAINT foreign_sid FOREIGN KEY (sensorid) REFERENCES sensors(id) ON DELETE CASCADE;

--
-- SERVERINFO
--
CREATE TABLE serverinfo (
    id serial NOT NULL,
    name character varying NOT NULL,
    value character varying NOT NULL,
    "timestamp" integer
);

ALTER TABLE ONLY serverinfo
    ADD CONSTRAINT serverinfo_name_key UNIQUE (name);
ALTER TABLE ONLY serverinfo
    ADD CONSTRAINT serverinfo_pkey PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE ON TABLE serverinfo TO idslog;
