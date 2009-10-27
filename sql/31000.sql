-- SURFids 3.10
-- Database conversion 3.00 -> 3.10
-- Changeset 001
-- 27-10-2009
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
