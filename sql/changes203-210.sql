-- SURFids 2.10.00
-- Database conversion 2.03 -> 2.10
-- Changeset 006
-- 03-10-2008
--

--
-- Changelog
-- 006 Added type conversion of severity.val
-- 005 Added pageconf, report_content, indexmods
-- 004 Removed report_content modification
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
ALTER TABLE report_content
    DROP CONSTRAINT foreign_report_content_login_id;

ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES "login"(id) ON DELETE CASCADE;

--
-- SEVERITY
--
ALTER TABLE severity ALTER val TYPE integer USING val::integer
