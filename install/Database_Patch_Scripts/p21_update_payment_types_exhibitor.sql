/* p21 - update payment types for exhibitor - add to enum the new types used by the exhibitor module
 */

alter table payments modify column category enum('reg', 'artshow', 'vendor', 'exhibits', 'other');

INSERT INTO patchLog(id, name) values(21, 'payment_types');
