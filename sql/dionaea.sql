--
-- SURFids 3.10
-- Dionaea SQL functions
-- Changeset 002
-- 07-05-2010
--

--
-- Version history
-- 002 Moved honeypots table creation up
-- 001 Initial release
--

--
-- TABLE honeypots
--

DROP TABLE IF EXISTS honeypots;
CREATE TABLE honeypots (
    id integer NOT NULL,
    name character varying NOT NULL,
    "desc" character varying
);

INSERT INTO honeypots VALUES (0, 'nepenthes', '');
INSERT INTO honeypots VALUES (1, 'argos', '');
INSERT INTO honeypots VALUES (2, 'snort', '');
INSERT INTO honeypots VALUES (3, 'glastopf', '');
INSERT INTO honeypots VALUES (4, 'amun', '');
INSERT INTO honeypots VALUES (5, 'dionaea', '');

ALTER TABLE ONLY honeypots
    ADD CONSTRAINT honeypots_pkey PRIMARY KEY (id);

GRANT SELECT ON TABLE honeypots TO idslog;
GRANT SELECT ON TABLE honeypots TO nepenthes;

--
-- FUNCTIONS
--

CREATE OR REPLACE FUNCTION surfids3_attack_add(integer, inet, integer, inet, integer, macaddr, integer) RETURNS integer
    AS $_$DECLARE
    p_severity  ALIAS FOR $1;
    p_attackerip    ALIAS FOR $2;
    p_attackerport  ALIAS FOR $3;
    p_decoyip   ALIAS FOR $4;
    p_decoyport ALIAS FOR $5;
    p_hwa       ALIAS FOR $6;
    p_atype     ALIAS FOR $7;
    m_attackid INTEGER;
    m_sensorid INTEGER;
BEGIN

    SELECT INTO m_sensorid surfids3_sensorid_get(p_decoyip);
    SELECT INTO m_attackid surfids3_attack_add_by_id(p_severity,
        p_attackerip, p_attackerport, p_decoyip,
        p_decoyport, p_hwa, m_sensorid, p_atype);

    return m_attackid;
END$_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_attack_add_by_id(integer, inet, integer, inet, integer, macaddr, integer, integer) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_attackerip    ALIAS FOR $2;
        p_attackerport  ALIAS FOR $3;
        p_decoyip       ALIAS FOR $4;
        p_decoyport     ALIAS FOR $5;
        p_hwa           ALIAS FOR $6;
        p_sensorid      ALIAS FOR $7;
        p_atype         ALIAS FOR $8;
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
                 src_mac,
                 atype)
        VALUES
                (p_severity,
                 extract(epoch from current_timestamp(0))::integer,
                 p_attackerip,
                 p_attackerport,
                 p_decoyip,
                 p_decoyport,
                 p_sensorid,
                 p_hwa,
                 p_atype);

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_attack_link(integer, integer, integer) RETURNS void
    AS $_$DECLARE
  p_parentid ALIAS FOR $1;
  p_childid  ALIAS FOR $2;
  p_treeid   ALIAS FOR $3;
BEGIN
        UPDATE attacks SET parentid = p_parentid, treeid = p_treeid WHERE id = p_childid;
        return;
END
$_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_attack_update_severity(integer, integer) RETURNS void
    AS $_$DECLARE
        p_attackid ALIAS FOR $1;
        p_severity ALIAS FOR $2;
BEGIN
        UPDATE attacks SET severity = p_severity WHERE id = p_attackid;
        return;
END$_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_detail_add(integer, inet, integer, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    p_localhost ALIAS FOR $2;
    p_type ALIAS FOR $3;
    p_data ALIAS FOR $4;

    m_sensorid INTEGER;
    m_check INTEGER;
    m_detailid INTEGER;
BEGIN
    SELECT INTO m_sensorid surfids3_sensorid_get(p_localhost);

        IF p_type = 1 OR p_type = 80 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = p_data;
          IF m_check = 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

    INSERT INTO details
        (attackid,sensorid,type,text)
    VALUES
        (p_attackid,m_sensorid,p_type,p_data);

    SELECT INTO m_detailid currval('details_id_seq');
    return m_detailid;
END$_$
    LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION surfids3_detail_add_by_id(integer, integer, integer, character varying) RETURNS integer
    AS $_$DECLARE
    p_attackid ALIAS FOR $1;
    m_sensorid ALIAS FOR $2;
    p_type ALIAS FOR $3;
    p_data ALIAS FOR $4;

    m_check INTEGER;
    m_detailid INTEGER;
BEGIN
        IF p_type = 1 OR p_type = 80 THEN
          SELECT COUNT(name) INTO m_check FROM stats_dialogue WHERE name = p_data;
          IF m_check = 0 THEN
            INSERT INTO stats_dialogue (name) VALUES (p_data);
          END IF;
        END IF;

    INSERT INTO details
        (attackid,sensorid,type,text)
    VALUES
        (p_attackid,m_sensorid,p_type,p_data);

    SELECT INTO m_detailid currval('details_id_seq');
    return m_detailid;
END$_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_detail_add_download(inet, inet, character varying, character varying, integer) RETURNS void
    AS $_$DECLARE
    p_remotehost ALIAS FOR $1;
    p_localhost ALIAS FOR $2;
    p_url ALIAS FOR $3;
    p_hash ALIAS FOR $4;
    p_atype ALIAS FOR $5;

    m_sensorid INTEGER;
    m_attackid INTEGER;
    m_check INTEGER;
BEGIN
    SELECT INTO m_sensorid surfids3_sensorid_get(p_localhost);
    SELECT INTO m_attackid surfids3_attack_add_by_id(32, p_remotehost, 0, p_localhost, 0, NULL, m_sensorid, p_atype);

        SELECT COUNT(name) INTO m_check FROM uniq_binaries WHERE name = p_hash;

        IF m_check = 0 THEN
          INSERT INTO uniq_binaries (name) VALUES (p_hash);
        END IF;

    PERFORM surfids3_detail_add_by_id(m_attackid,m_sensorid,4,p_url);
    PERFORM surfids3_detail_add_by_id(m_attackid,m_sensorid,8,p_hash);

    return;
END;    $_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_detail_add_offer(inet, inet, character varying, integer) RETURNS void
    AS $_$DECLARE
    p_remotehost ALIAS FOR $1;
    p_localhost ALIAS FOR $2;
    p_url ALIAS FOR $3;
    p_atype ALIAS FOR $4;

    m_sensorid INTEGER;
    m_attackid INTEGER;
BEGIN
    SELECT INTO m_sensorid surfids3_sensorid_get(p_localhost);
    SELECT INTO m_attackid surfids3_attack_add_by_id(16, p_remotehost, 0, p_localhost, 0, NULL, m_sensorid, p_atype);

    PERFORM surfids3_detail_add_by_id(m_attackid,m_sensorid,4,p_url);
    return;
END;    $_$
    LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION surfids3_sensorid_get(inet) RETURNS integer
    AS $_$DECLARE
        p_localhost ALIAS FOR $1;
        m_sensorid  INTEGER;
BEGIN
        SELECT INTO m_sensorid id FROM sensors WHERE tapip >>= p_localhost;
        return m_sensorid;
END
$_$
    LANGUAGE plpgsql;
