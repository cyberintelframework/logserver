-- SURFids 3.12
-- Database conversion 3.10+ -> 3.12
--

--
-- Changelog
-- 001 Initial release
--

--
-- VERSION
--
GRANT SELECT ON TABLE version TO idslog;

--
-- function surfids3_sshversion_add
--
DROP FUNCTION surfids3_sshversion_add(integer, integer);
CREATE OR REPLACE FUNCTION surfids3_sshversion_add(integer, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_version ALIAS FOR $2;

    m_versionid INTEGER;
    m_check INTEGER;
BEGIN

    SELECT COUNT(id) INTO m_check FROM uniq_sshversion WHERE version = p_version;
    IF m_check = 0 THEN
      INSERT INTO uniq_sshversion (version) VALUES (p_version);
    END IF;
    SELECT id INTO m_versionid FROM uniq_sshversion WHERE version = p_version;

    INSERT INTO ssh_version
        (attackid,version)
    VALUES
        (p_attackid,m_versionid);

    SELECT INTO m_versionid currval('ssh_version_id_seq');
    return m_versionid;
END$_$
    LANGUAGE plpgsql;
