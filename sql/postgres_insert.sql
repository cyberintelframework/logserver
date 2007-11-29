--
-- SURFnet IDS database structure
-- Version 2.00.05
-- 25-10-2007
--

-- Version history
-- 2.00.05 Updated F-prot commands
-- 2.00.04 Updated BitDefender commands
-- 2.00.03 Added scheme record for p0f-db
-- 2.00.02 Added argos_templates stuff
-- 2.00.01 version 2.00

--
-- ARGOS_TEMPLATES
--
SELECT pg_catalog.setval(pg_catalog.pg_get_serial_sequence('argos_templates', 'id'), 4, true);

INSERT INTO argos_templates VALUES (1, 'All Traffic', 'all');
INSERT INTO argos_templates VALUES (2, 'Top 100 of all your sensors', 'top100org');
INSERT INTO argos_templates VALUES (3, 'Top 100 of all sensors', 'top100all');
INSERT INTO argos_templates VALUES (4, 'Top 100 sensor', 'top100sensor');

--
-- LOGIN
--
INSERT INTO login (id, username, password, email, organisation, access) VALUES (nextval('login_id_seq'::regclass), 'admin', '21232f297a57a5a743894a0e4a801fc3', 'root@localhost', 1, '999');

--
-- LOGMESSAGES
--
INSERT INTO logmessages VALUES (1, 30, 'Sensor starting up!');
INSERT INTO logmessages VALUES (3, 30, 'Sensor stopped!');
INSERT INTO logmessages VALUES (4, 30, 'Disabled SSH!');
INSERT INTO logmessages VALUES (5, 30, 'Enabled SSH!');
INSERT INTO logmessages VALUES (6, 30, 'Enabled sensor!');
INSERT INTO logmessages VALUES (7, 30, 'Disabled sensor!');
INSERT INTO logmessages VALUES (8, 30, 'Rebooting sensor!');
INSERT INTO logmessages VALUES (11, 30, 'Sensor started!');
INSERT INTO logmessages VALUES (12, 30, 'Network configuration changed!');
INSERT INTO logmessages VALUES (2, 40, 'Warning: No static IP configuration on the server!');
INSERT INTO logmessages VALUES (9, 50, 'Error: No tap device present!');
INSERT INTO logmessages VALUES (10, 50, 'Error: Tap device could not get an IP address!');
INSERT INTO logmessages VALUES (13, 20, 'Sensor local IP address changed!');
INSERT INTO logmessages VALUES (14, 20, 'Sensor remote IP address changed!');

--
-- ORGANISATIONS
--
INSERT INTO organisations (id, organisation) VALUES (nextval('organisations_id_seq'::regclass), 'ADMIN');

--
-- SCANNERS
--
INSERT INTO scanners VALUES (5, 'F-Prot', '/opt/f-prot/fpscan --report !bindir!/!file! | grep !file! |  sed -e ''s/.* <//g'' -e ''s/ .*//g''', '/opt/f-prot/tools/check-updates.pl', 1, '/opt/f-prot/fpscan --version  | grep -i "Antivirus version" | sed -e ''s/.*version //g''', '');
INSERT INTO scanners VALUES (6, 'Kaspersky', '/opt/kav/5.5/kav4unix/bin/kavscanner !bindir!/!file! | grep !file! | tail -n1 | awk ''{print $NF}'' | grep -v ^OK$', '/opt/kav/5.5/kav4unix/bin/keepup2date', 1, '/opt/kav/5.5/kav4unix/bin/kavscanner -v | grep -i version', '');
INSERT INTO scanners VALUES (4, 'AVAST', '/opt/avast4workstation-1.0.8/bin/avast !bindir!/!file! | grep !file! | grep -v "\\[OK\\]" | tail -n1 | awk -F "infected by:" ''{print $2}'' | awk ''{print $1}'' | awk -F "]" ''{print $1}''', '/opt/avast4workstation-1.0.8/bin/avast-update', 1, '/opt/avast4workstation-1.0.8/bin/avast --version | head -n1', '');
INSERT INTO scanners VALUES (3, 'BitDefender', 'bdscan --files !bindir!/!file! | grep !file! | awk '{print $3}'', 'bdscan --update', 1, 'bdscan -info | head -n1', '');
INSERT INTO scanners VALUES (2, 'Antivir', 'antivir -rs !bindir!/!file! | grep !file! | awk ''{print $2}'' | awk -F [ ''{print $2}'' | awk -F ] ''{print $1}''', 'antivir --update', 1, 'antivir --version | head -n1', '');
INSERT INTO scanners VALUES (1, 'ClamAV', 'clamscan --no-summary !bindir!/!file! | grep !file! | awk ''{print $2}'' | grep -v ^OK$', 'freshclam', 1, 'clamscan --version', '');

--
-- SEVERITY
--
INSERT INTO severity VALUES (1, '0 ', 'Possible malicious attack');
INSERT INTO severity VALUES (2, '1 ', 'Malicious attack');
INSERT INTO severity VALUES (3, '16', 'Malware offered');
INSERT INTO severity VALUES (4, '32', 'Malware downloaded');

--
-- SCHEME
--
INSERT INTO scheme (version, created) VALUES (1002, CURRENT_TIMESTAMP);
