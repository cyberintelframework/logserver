-- SURFids 3.10
-- Database conversion 3.06+ -> 3.10
-- Changeset 001
-- 11-08-2010
--

--
-- Changelog
-- 001 Initial release
--

--
-- SENSORS
--
ALTER TABLE sensors ALTER COLUMN arp DROP DEFAULT, ALTER COLUMN arp TYPE boolean USING arp::boolean, ALTER COLUMN arp SET DEFAULT false;
ALTER TABLE sensors ADD COLUMN dhcp boolean DEFAULT false NOT NULL;
ALTER TABLE sensors ADD COLUMN ipv6 boolean DEFAULT false NOT NULL;
ALTER TABLE sensors ADD COLUMN protos boolean DEFAULT false NOT NULL;

--
-- DHCP_STATIC
--
CREATE TABLE dhcp_static (
    id integer NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

CREATE SEQUENCE dhcp_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE dhcp_static_id_seq OWNED BY dhcp_static.id;
ALTER TABLE dhcp_static ALTER COLUMN id SET DEFAULT nextval('dhcp_static_id_seq'::regclass);
ALTER TABLE ONLY dhcp_static ADD CONSTRAINT dhcp_static_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE dhcp_static TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE dhcp_static_id_seq TO idslog;

--
-- IPV6_STATIC
--
CREATE TABLE ipv6_static (
    id integer NOT NULL,
    ip inet NOT NULL,
    sensorid integer NOT NULL
);

CREATE SEQUENCE ipv6_static_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER SEQUENCE ipv6_static_id_seq OWNED BY ipv6_static.id;

ALTER TABLE ipv6_static ALTER COLUMN id SET DEFAULT nextval('ipv6_static_id_seq'::regclass);
ALTER TABLE ONLY ipv6_static ADD CONSTRAINT ipv6_static_pkey PRIMARY KEY (id);

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE ipv6_static TO idslog;
GRANT SELECT,UPDATE ON SEQUENCE ipv6_static_id_seq TO idslog;

--
-- surfids3_ipv6_add_by_id
--
CREATE OR REPLACE FUNCTION surfids3_ipv6_add_by_id(integer, inet, integer, integer) RETURNS integer
    AS $_$DECLARE
        p_sensorid      ALIAS FOR $1;
        p_sourceip      ALIAS FOR $2;
        p_severity      ALIAS FOR $3;
        p_atype         ALIAS FOR $4;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks (sensorid, timestamp, src_mac, source, severity, atype)
        VALUES
                (p_sensorid,
                 extract(epoch from current_timestamp(0))::integer,
                 p_sourceip,
                 p_severity,
                 p_atype
        );

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- surfids3_arp_add_by_id
--
CREATE OR REPLACE FUNCTION surfids3_arp_add_by_id(integer, macaddr, macaddr, inet, inet, integer, integer) RETURNS integer
    AS $_$DECLARE
        p_severity      ALIAS FOR $1;
        p_dstmac        ALIAS FOR $2;
        p_srcmac        ALIAS FOR $3;
        p_dstip         ALIAS FOR $4;
        p_srcip         ALIAS FOR $5;
        p_sensorid      ALIAS FOR $6;
        p_atype         ALIAS FOR $7;
        m_attackid      INTEGER;
BEGIN
        INSERT INTO attacks
                (severity,
                 timestamp,
                 src_mac,
                 dst_mac,
                 source,
                 dest,
                 sensorid,
                 atype)
        VALUES
                (p_severity,
                 extract(epoch from current_timestamp(0))::integer,
                 p_srcmac,
                 p_dstmac,
                 p_dstip,
                 p_srcip,
                 p_sensorid,
                 p_atype);

        SELECT INTO m_attackid currval('attacks_id_seq');
        return m_attackid;
END$_$
    LANGUAGE plpgsql;

--
-- VERSION
--
UPDATE version SET version = '31000';
