ALTER TABLE ONLY organisations ADD CONSTRAINT unique_organisations UNIQUE (organisation);
INSERT INTO organisations (id, organisation) VALUES (nextval('organisations_id_seq'::regclass), 'NEPENTHES');
UPDATE sensors SET organisation = (SELECT id FROM organisations WHERE organisation = 'NEPENTHES') WHERE organisation = 0;
