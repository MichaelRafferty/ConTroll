/* P5-add foreign keys */

/* agelist */
ALTER TABLE ageList ADD CONSTRAINT ageList_conid_fk
FOREIGN KEY (conid) references conlist(id)ON UPDATE CASCADE;

/* artItems */
ALTER TABLE artItems ADD CONSTRAINT artItems_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE artItems ADD CONSTRAINT artItems_artshow_fk 
FOREIGN KEY (artshow) references artshow(id) ON UPDATE CASCADE;

/* artist*/
ALTER TABLE artist ADD CONSTRAINT artist_vendor_fk 
FOREIGN KEY (vendor) references vendors(id) ON UPDATE CASCADE;
ALTER TABLE artist ADD CONSTRAINT artist_artist_fk 
FOREIGN KEY (artist) references perinfo(id) ON UPDATE CASCADE;

/* artsales */
ALTER TABLE artsales ADD CONSTRAINT artsales_transid_fk 
FOREIGN KEY (transid) references transaction(id) ON UPDATE CASCADE;
ALTER TABLE artsales ADD CONSTRAINT artsales_artitem_fk 
FOREIGN KEY (artid) references artItems(id) ON UPDATE CASCADE;
ALTER TABLE artsales ADD CONSTRAINT artsales_perinfo_fk 
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;

/* artshow, need help? */
ALTER TABLE artshow ADD CONSTRAINT artshow_perinfo_fk 
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT artshow_conid_fk 
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT artshow_artid_fk 
FOREIGN KEY (artid) references artist(id) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT artshow_agent_fk 
FOREIGN KEY (agent) references perinfo(id) ON UPDATE CASCADE;

/* artshow_reg */
ALTER TABLE artshow_reg ADD CONSTRAINT artshow_reg_conid_fk 
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;

/* atcon -> obsolete, DELETE AFTER CREATING atcon_history  */
/* DROP TABLE ATCON; */

/* atcon_auth */
ALTER TABLE atcon_auth ADD CONSTRAINT atcon_authuser_fk 
FOREIGN KEY (authuser) references atcon_user(id) ON UPDATE CASCADE ON DELETE CASCADE;

/* atcon_badge -> obsolete, DELETE AFTER CREATING atcon_history */

/* atcon_user */
ALTER TABLE atcon_user ADD CONSTRAINT atcon_user_conid_fk 
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE atcon_user ADD CONSTRAINT atcon_user_perid_fk 
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE ON DELETE CASCADE;

/* auth */
/* none? */

/* badgeList */
ALTER TABLE badgeList ADD CONSTRAINT badgeList_userid_fk
FOREIGN KEY (userid) references user(id) ON UPDATE CASCADE;
ALTER TABLE badgeList ADD CONSTRAINT badgeList_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE badgeList ADD CONSTRAINT badgeList_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;

/* conlist */
/* none */

/* memCategories – to create*/ HERE!!!
insert ignore into memCategories select distinct memCategory, 1, 'Y' from memList;
insert into ageList (conid, ageType, label, shortname, sortorder) select conid, memAge, memAge, memAge, 999 from memList where conid<57;

/* memLabel */
/* view */

/* memTypes – to create*/
insert ignore into memTypes select distinct memType, 1, 'Y' from memList;

/* memList */
ALTER TABLE memList ADD CONSTRAINT memList_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT memList_memCategory_fk
FOREIGN KEY (memCategory) references memCategories(memCategory) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT memList_memType_fk
FOREIGN KEY (memType) references memTypes(memType) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT memList_memAge_fk
FOREIGN KEY (conid, memAge) references ageList(conid, ageType) ON UPDATE CASCADE;

/* newperson */
ALTER TABLE newperson ADD CONSTRAINT newperson_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE newperson ADD CONSTRAINT newperson_transid_fk
FOREIGN KEY (transid) references transaction(id) ON UPDATE CASCADE;

/* oauth_links */
ALTER TABLE oauth_links ADD CONSTRAINT oauth_links_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;

/* oauth_people */
ALTER TABLE oauth_people ADD CONSTRAINT oauth_people_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;

/* payments */
ALTER TABLE payments ADD CONSTRAINT payments_transid_fk
FOREIGN KEY (transid) references transaction(id) ON UPDATE CASCADE;
ALTER TABLE payments ADD CONSTRAINT payments_userid_fk
FOREIGN KEY (userid) references user(id) ON UPDATE CASCADE;
ALTER TABLE payments ADD CONSTRAINT payments_cashier_fk
FOREIGN KEY (cashier) references perinfo(id) ON UPDATE CASCADE;

/* perinfo */
/* none */

/* printers */
/* already has foreign key from the creation section above */

/* psfs, bsfs, etc. should be renamed club */
ALTER TABLE club ADD CONSTRAINT club_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;

/* reg */
ALTER TABLE reg ADD CONSTRAINT reg_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT reg_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT reg_newperid_fk
FOREIGN KEY (newperid) references newperson(id) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT reg_create_trans_fk
FOREIGN KEY (create_trans) references transaction(id) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT reg_pickup_trans_fk
FOREIGN KEY (pickup_trans) references transaction(id) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT reg_memId_fk
FOREIGN KEY (memId) references memList(id) ON UPDATE CASCADE;

/* ALTER TABLE reg ADD CONSTRAINT reg_create_user_fk
FOREIGN KEY (create_user) references perinfo(id) ON UPDATE CASCADE; */

/* server */
/* none */

/* transaction */
ALTER TABLE transaction ADD CONSTRAINT transaction_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE transaction ADD CONSTRAINT transaction_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE transaction ADD CONSTRAINT transaction_newperid_fk
FOREIGN KEY (newperid) references newperson(id) ON UPDATE CASCADE;

/* user */
/* none - needs to tie to perid */

/* user_auth */
ALTER TABLE user_auth ADD CONSTRAINT user_auth_auth_id_fk
FOREIGN KEY (auth_id) references auth(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE user_auth ADD CONSTRAINT user_auth_user_id_fk
FOREIGN KEY (user_id) references user(id) ON UPDATE CASCADE ON DELETE CASCADE;

/* vendor_reg */
ALTER TABLE vendor_reg ADD CONSTRAINT vendor_reg_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;

/* vendor_show */
ALTER TABLE vendor_show ADD CONSTRAINT vendor_show_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;
ALTER TABLE vendor_show ADD CONSTRAINT vendor_show_vendor_fk
FOREIGN KEY (vendor) references vendors(id) ON UPDATE CASCADE;
ALTER TABLE vendor_show ADD CONSTRAINT vendor_show_transid_fk
FOREIGN KEY (transid) references transaction(id) ON UPDATE CASCADE;

/* vendors */
/* none */

/* voln, obsolete? Drop? */ /* YES DROP */
ALTER TABLE voln ADD CONSTRAINT voln_perid_fk
FOREIGN KEY (perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE voln ADD CONSTRAINT voln_newperid_fk
FOREIGN KEY (newperid) references newperson(id) ON UPDATE CASCADE;
ALTER TABLE voln ADD CONSTRAINT voln_conid_fk
FOREIGN KEY (conid) references conlist(id) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) values(6, 'Foreign Keys');

