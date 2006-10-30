INSERT INTO login (id, username, password, email, organisation, access) VALUES (1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'root@localhost', 1, '999');
SELECT setval('login_id_seq', 1, true);

INSERT INTO organisations (id, organisation) VALUES (1, 'ADMIN');
SELECT setval('organisations_id_seq', 1, true);

INSERT INTO severity VALUES (1, '0 ', 'Possible malicious attack');
INSERT INTO severity VALUES (2, '1 ', 'Malicious attack');
INSERT INTO severity VALUES (3, '16', 'Malware offered');
INSERT INTO severity VALUES (4, '32', 'Malware downloaded');

