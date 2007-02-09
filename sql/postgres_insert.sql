INSERT INTO login (id, username, password, email, organisation, access) VALUES (nextval('login_id_seq'::regclass), 'admin', '21232f297a57a5a743894a0e4a801fc3', 'root@localhost', 1, '999');

INSERT INTO organisations (id, organisation) VALUES (nextval('organisations_id_seq'::regclass), 'ADMIN');

INSERT INTO severity VALUES (1, '0 ', 'Possible malicious attack');
INSERT INTO severity VALUES (2, '1 ', 'Malicious attack');
INSERT INTO severity VALUES (3, '16', 'Malware offered');
INSERT INTO severity VALUES (4, '32', 'Malware downloaded');

