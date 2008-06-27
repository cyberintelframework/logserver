-- SURFids SQL changes for 1.03
-- Version: 1.03.03
-- 13-03-2007

-- Changelog
-- Removed the timeunit from table login
-- 1.03.03 Added column subject to table report_content
-- 1.03.02 Removed table report and added gpg to table login
-- 1.03.01 Initial release

CREATE TABLE sessions (
    id serial NOT NULL,
    sid character varying NOT NULL,
    ip inet NOT NULL,
    ts integer NOT NULL,
    username character varying
);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE sessions TO idslog;

GRANT SELECT,UPDATE ON TABLE sessions_id_seq TO idslog;

CREATE TABLE rrd (
    id serial NOT NULL,
    orgid integer NOT NULL,
    "type" character varying NOT NULL,
    label character varying NOT NULL,
    image character varying NOT NULL,
    "timestamp" integer
);

ALTER TABLE ONLY rrd
    ADD CONSTRAINT primary_rrd PRIMARY KEY (id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE rrd TO idslog;

GRANT SELECT,UPDATE ON TABLE rrd_id_seq TO idslog;

CREATE TABLE report_content (
    id serial NOT NULL,
    user_id integer,
    title character varying,
    "template" integer,
    last_sent integer,
    active boolean,
    sensor_id integer,
    frequency integer,
    "interval" integer,
    priority integer,
    subject character varying
);

ALTER TABLE ONLY report_content
    ADD CONSTRAINT primary_report_content PRIMARY KEY (id);

ALTER TABLE ONLY report_content
    ADD CONSTRAINT foreign_report_content_login_id FOREIGN KEY (user_id) REFERENCES login(id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE report_content TO idslog;

GRANT SELECT,UPDATE ON TABLE report_content_id_seq TO idslog;

CREATE TABLE report_template_threshold (
    id serial NOT NULL,
    report_content_id integer NOT NULL,
    target integer,
    value integer,
    deviation integer,
    "operator" integer
);

ALTER TABLE ONLY report_template_threshold
    ADD CONSTRAINT primary_report_template_threshold PRIMARY KEY (id);

ALTER TABLE ONLY report_template_threshold
    ADD CONSTRAINT foreign_report_template_threshold_report_content_id FOREIGN KEY (report_content_id) REFERENCES report_content(id);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE report_template_threshold TO idslog;

GRANT SELECT,UPDATE ON TABLE report_template_threshold_id_seq TO idslog;


CREATE TABLE org_id (
    id serial NOT NULL,
    orgid integer NOT NULL,
    identifier character varying NOT NULL
);

ALTER TABLE ONLY org_id
    ADD CONSTRAINT primary_org_id_id PRIMARY KEY (id);

ALTER TABLE ONLY org_id
    ADD CONSTRAINT unique_identifier UNIQUE (identifier);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE org_id TO idslog;

GRANT SELECT,UPDATE ON TABLE org_id_id_seq TO idslog;

ALTER TABLE login ADD COLUMN serverhash character varying;
ALTER TABLE login ADD COLUMN gpg integer;

