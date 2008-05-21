-- SURFids 2.10.00
-- Database conversion 2.02 -> 2.10
-- Changeset 003
-- 21-05-2008
--

--
-- Changelog
-- 003 Added report_content modification
-- 002 Added LOGIN modifications
-- 001 Initial release
--

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
-- LOGIN
--
ALTER TABLE login ADD COLUMN d_plotter integer DEFAULT 0 NOT NULL;
ALTER TABLE login ADD COLUMN d_plottype integer DEFAULT 1 NOT NULL;
ALTER TABLE login ADD COLUMN d_utc integer DEFAULT 0 NOT NULL;

--
-- REPORT_CONTENT
--
ALTER TABLE report_content ADD COLUMN always integer DEFAULT 0 NOT NULL;
ALTER TABLE report_content ADD COLUMN utc integer DEFAULT 0 NOT NULL;
