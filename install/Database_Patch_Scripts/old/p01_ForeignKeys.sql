/* P1- ATCON AUTH Changes */

alter table atcon_auth modify column passwd varchar(256) null;
alter table atcon_auth add column userhash varchar(256) null;
rename table atcon_auth to atcon_user;
create table atcon_auth (
      id int not null auto_increment,
	authuser int not null,
auth enum('data_entry','register','cashier','manager','artinventory', 'artshow','artsales','vol_roll') NOT NULL,
	PRIMARY KEY(id)
);
alter table atcon_auth add constraint atcon_auth_user
      FOREIGN KEY (authuser)
	REFERENCES atcon_user (id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;
 
insert into atcon_auth(authuser, auth)
select p.id, a.auth
FROM (
select MIN(id) id, perid, conid
from atcon_user
GROUP BY perid, conid
	) p
JOIN atcon_user a ON (p.perid = a.perid and a.conid = p.conid)
order by id, auth;
delete from atcon_user
where id not in (
select authuser from atcon_auth
);
UPDATE atcon_user SET userhash = MD5(concat(id,perid));
alter table atcon_user drop column auth;

ALTER TABLE reg drop column question;
ALTER TABLE reg drop column query;

INSERT INTO patchLog(id, name) values(1, 'ATCON Auth Changes');
