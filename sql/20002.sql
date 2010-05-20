-- SURFids 3.00
-- Database conversion 2.00.02 -> 2.00.03
-- Changeset 001
-- 01-02-2008
--

-- Changelog
-- 001 Initial release

--
-- OSTYPES
--
SELECT DISTINCT split_part(name, ' ', 1) as os INTO ostypes FROM system ORDER BY os ASC;

ALTER TABLE ostypes ADD COLUMN id SERIAL;

ALTER TABLE ONLY ostypes
    ADD CONSTRAINT ostypes_pkey PRIMARY KEY (id);

ALTER TABLE ONLY ostypes
    ADD CONSTRAINT unique_os UNIQUE (os);

GRANT INSERT,SELECT ON TABLE ostypes TO pofuser;
GRANT SELECT ON TABLE ostypes TO idslog;

GRANT SELECT ON TABLE ostypes_id_seq TO idslog;
GRANT SELECT,UPDATE ON TABLE ostypes_id_seq TO pofuser;


--
-- SYSTEM
--
CREATE RULE insert_name AS ON INSERT TO "system" WHERE (NOT (split_part((new.name)::text, ' '::text, 1) IN (SELECT ostypes.os FROM ostypes))) DO INSERT INTO ostypes (os) VALUES (split_part((new.name)::text, ' '::text, 1));
