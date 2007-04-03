--
-- SURFnet IDS Nepenthes functions
-- Version 1.04.02
--

--
-- 1.04.02 Modifed surfnet_detail_add functions
-- 1.04.01 Initial release
--

CREATE PROCEDURAL LANGUAGE plpgsql;

--
-- SURFNET_ATTACK_ADD
--
CREATE FUNCTION surfnet_attack_add(integer, inet, integer, inet, integer, macaddr, inet) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_attackerip    ALIAS FOR $2;
        p_attackerport  ALIAS FOR $3;
        p_decoyip       ALIAS FOR $4;
        p_decoyport     ALIAS FOR $5;
        p_hwa           ALIAS FOR $6;
        p_localhost     ALIAS FOR $7;
        m_attackid INTEGER;
        m_sensorid INTEGER;
BEGIN

        SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
        SELECT INTO m_attackid surfnet_attack_add_by_id(p_severity,
                p_attackerip, p_attackerport, p_decoyip,
                p_decoyport, p_hwa, m_sensorid);

        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- SURFNET_ATTACK_ADD_BY_ID
--
CREATE FUNCTION surfnet_attack_add_by_id(integer, inet, integer, inet, integer, macaddr, integer) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_attackerip    ALIAS FOR $2;
        p_attackerport  ALIAS FOR $3;
        p_decoyip       ALIAS FOR $4;
        p_decoyport     ALIAS FOR $5;
        p_hwa           ALIAS FOR $6;
        p_sensorid      ALIAS FOR $7;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks
                (severity,
                 timestamp,
                 source,
                 sport,
                 dest,
                 dport,
                 sensorid,
                 src_mac)
        VALUES
                (p_severity,
                 extract(epoch from current_timestamp(0))::integer,
                 p_attackerip,
                 p_attackerport,
                 p_decoyip,
                 p_decoyport,
                 p_sensorid,
                 p_hwa);

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- SURFNET_ATTACK_UPDATE_SEVERITY
--
CREATE FUNCTION surfnet_attack_update_severity(integer, integer) RETURNS void
    AS $_$DECLARE
        p_attackid ALIAS FOR $1;
        p_severity ALIAS FOR $2;
BEGIN
        UPDATE attacks SET severity = p_severity WHERE id = p_attackid;
        return;
END$_$
    LANGUAGE plpgsql;

--
-- SURFNET_DETAIL_ADD
--
CREATE FUNCTION surfnet_detail_add(integer, inet, integer, character varying) RETURNS void
    AS $_$DECLARE
        p_attackid ALIAS FOR $1;
        p_localhost ALIAS FOR $2;
        p_type ALIAS FOR $3;
        p_data ALIAS FOR $4;

        m_sensorid INTEGER;
BEGIN
        SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);

        IF p_type = 1 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = 'p_data';
          IF m_check > 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

        INSERT INTO details
                (attackid,sensorid,type,text)
        VALUES
                (p_attackid,m_sensorid,p_type,p_data);
END$_$
    LANGUAGE plpgsql;

--
-- SURFNET_DETAIL_ADD_BY_ID
--
CREATE FUNCTION surfnet_detail_add_by_id(integer, integer, integer, character varying) RETURNS void
    AS $_$DECLARE
        p_attackid ALIAS FOR $1;
        m_sensorid ALIAS FOR $2;
        p_type ALIAS FOR $3;
        p_data ALIAS FOR $4;
BEGIN
        IF p_type = 1 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = 'p_data';
          IF m_check > 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

        INSERT INTO details
                (attackid,sensorid,type,text)
        VALUES
                (p_attackid,m_sensorid,p_type,p_data);
END$_$
    LANGUAGE plpgsql;

--
-- SURFNET_DETAIL_ADD_DOWNLOAD
--
CREATE FUNCTION surfnet_detail_add_download(inet, inet, character varying, character varying) RETURNS void
    AS $_$DECLARE
        p_remotehost ALIAS FOR $1;
        p_localhost ALIAS FOR $2;
        p_url ALIAS FOR $3;
        p_hash ALIAS FOR $4;

        m_sensorid INTEGER;
        m_attackid INTEGER;
BEGIN
        SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
        SELECT INTO m_attackid surfnet_attack_add_by_id(32,p_remotehost, 0,
                p_localhost, 0,
                NULL,m_sensorid);

        SELECT COUNT(name) INTO m_check FROM uniq_binaries WHERE name = 'p_hash';

        IF m_check > 0 THEN
          INSERT INTO uniq_binaries (name) VALUES (p_hash); 
        END IF;

        PERFORM surfnet_detail_add_by_id(m_attackid,
                                m_sensorid,4,p_url);
        PERFORM surfnet_detail_add_by_id(m_attackid,
                                m_sensorid,8,p_hash);

        return;
END;    $_$
    LANGUAGE plpgsql;

--
-- SURFNET_DETAIL_ADD_OFFER
--
CREATE FUNCTION surfnet_detail_add_offer(inet, inet, character varying) RETURNS void
    AS $_$DECLARE
        p_remotehost ALIAS FOR $1;
        p_localhost ALIAS FOR $2;
        p_url ALIAS FOR $3;

        m_sensorid INTEGER;
        m_attackid INTEGER;
BEGIN
        SELECT INTO m_sensorid surfnet_sensorid_get(p_localhost);
        SELECT INTO m_attackid surfnet_attack_add_by_id(16,p_remotehost, 0,
                p_localhost, 0,
                NULL,m_sensorid);

        PERFORM surfnet_detail_add_by_id(m_attackid,
                                m_sensorid,4,p_url);
        return;
END;    $_$
    LANGUAGE plpgsql;

--
-- SURFNET_SENSORID_GET
--
CREATE FUNCTION surfnet_sensorid_get(inet) RETURNS integer
    AS $_$DECLARE
  p_localhost ALIAS FOR $1;
  m_sensorid  INTEGER;
BEGIN
        SELECT INTO m_sensorid id FROM sensors WHERE tapip = p_localhost;
        return m_sensorid;
END
$_$
    LANGUAGE plpgsql;
