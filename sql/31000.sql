-- SURFids 3.10
-- Database conversion 3.00 -> 3.10
-- Changeset 002
-- 04-12-2009
--

--
-- Changelog
-- 001 Initial release
--

--
-- SNIFF_PROTOS
--

ALTER TABLE sniff_protos DROP COLUMN protocol;
ALTER TABLE sniff_protos ADD COLUMN subtype integer;
ALTER TABLE sniff_protos ADD COLUMN version integer;

--
-- REPORT_CONTENT
--

ALTER TABLE report_content ADD COLUMN public boolean DEFAULT false NOT NULL;
ALTER TABLE report_content ADD COLUMN orgid integer DEFAULT 0 NOT NULL;
