--
-- SURFnet IDS database structure
-- Version 2.00.01
-- 14-09-2007
--

-- Version history
-- 2.00.01 version 2.00

--
-- ORGANISATIONS
--
ALTER TABLE ONLY organisations ADD CONSTRAINT unique_organisations UNIQUE (organisation);
INSERT INTO organisations (id, organisation) VALUES (nextval('organisations_id_seq'::regclass), 'NEPENTHES');
UPDATE sensors SET organisation = (SELECT id FROM organisations WHERE organisation = 'NEPENTHES') WHERE organisation = 0;
