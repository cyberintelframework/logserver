INSERT INTO login (id, username, password, email, organisation, access) VALUES (nextval('login_id_seq'::regclass), 'admin', '21232f297a57a5a743894a0e4a801fc3', 'root@localhost', 1, '999');

INSERT INTO organisations (id, organisation) VALUES (nextval('organisations_id_seq'::regclass), 'ADMIN');

INSERT INTO severity VALUES (1, '0 ', 'Possible malicious attack');
INSERT INTO severity VALUES (2, '1 ', 'Malicious attack');
INSERT INTO severity VALUES (3, '16', 'Malware offered');
INSERT INTO severity VALUES (4, '32', 'Malware downloaded');

INSERT INTO scanners VALUES (1, 'ClamAV', 'clamscan --no-summary !bindir!/!file! | grep !file! | awk ''{print $2}'' | grep -v ^OK$', 'freshclam', 0);
INSERT INTO scanners VALUES (2, 'Antivir', 'antivir -rs !bindir!/!file! | grep !file! | awk ''{print $2}'' | awk -F [ ''{print $2}''', 'antivir --update', 0);
INSERT INTO scanners VALUES (3, 'BitDefender', 'bdc --files !bindir!/!file! | grep !file! | awk ''{print $3}''', 'bdc --update', 0);
INSERT INTO scanners VALUES (4, 'AVAST', '/opt/avast4workstation-1.0.7/bin/avast !bindir!/!file! | grep !file! | grep -v "\\[OK\\]" | tail -n1 | awk -F "infected by:" ''{print $2}'' | awk ''{print $1}'' | awk -F "]" ''{print $1}''', '/opt/avast4workstation-1.0.7/bin/avast-update', 0);
INSERT INTO scanners VALUES (5, 'F-Prot', 'f-prot !bindir!/!file! | grep !file! | grep -v Search: | grep -vi error | awk ''{print $NF}''', '/opt/f-prot/tools/check-updates.pl', 0);
INSERT INTO scanners VALUES (6, 'Kaspersky', '/opt/kav/5.5/kav4unix/bin/kavscanner !bindir!/!file! | grep !file! | tail -n1 | awk ''{print $NF}'' | grep -v ^OK$', '/opt/kav/5.5/kav4unix/bin/keepup2date', 0);
