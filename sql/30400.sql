-- SURFids 3.10
-- Database conversion 3.00 -> 3.10
-- Changeset 003
-- 15-03-2010
--

--
-- Changelog
-- 003 Updated version
-- 002 Added mod_malhosts.php to indexmods
-- 001 Initial release
--

--
-- SNIFF_PROTOS
--

ALTER TABLE sniff_protos DROP COLUMN protocol;
ALTER TABLE sniff_protos ADD COLUMN subtype integer;

--
-- REPORT_CONTENT
--

ALTER TABLE report_content ADD COLUMN public boolean DEFAULT false NOT NULL;
ALTER TABLE report_content ADD COLUMN orgid integer DEFAULT 0 NOT NULL;

--
-- INDEXMODS
--
INSERT INTO indexmods (phppage) VALUES ('mod_topcountries.php');
INSERT INTO indexmods (phppage) VALUES ('mod_malhosts.php');

--
-- VERSION
--
UPDATE version SET version = '30400';
