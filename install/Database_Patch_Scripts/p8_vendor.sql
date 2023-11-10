/* P8-new Vendor interface */

CREATE TABLE vendors (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(64)DEFAULT NULL,
  website varchar(256)DEFAULT NULL,
  description text,
  email varchar(64) NOT NULL,
  password varchar(64),
  need_new tinyint(1) DEFAULT '1',
  confirm tinyint(1) DEFAULT '0',
  publicity tinyint(1) DEFAULT '0',
  addr varchar(64),
  addr2 varchar(64),
  city varchar(32),
  state varchar(2),
  zip varchar(10),
  PRIMARY KEY (id)
);

CREATE TABLE vendorSpaces (
	id int not null auto_increment,
	conid int not null,
	spaceType enum('artshow', 'dealers', 'fan', 'virtual') NOT NULL DEFAULT 'dealers',
	shortname varchar(32) not null,
	Name varchar(128) not null,
	description text default null,
	unitsAvailable int not null default 0,
	includedMemId int not null,
	additionalMemId int not null,
	PRIMARY KEY (id)
);

ALTER TABLE vendorSpaces add constraint vendorSpace_memList_i
	FOREIGN KEY (includedMemId)
	REFERENCES memList(id)
	ON UPDATE CASCADE;


ALTER TABLE vendorSpaces add constraint vendorSpace_memList_a
	FOREIGN KEY (additionalMemId)
	REFERENCES memList(id)
	ON UPDATE CASCADE;

CREATE TABLE vendorSpacePrices (
	id int not null auto_increment,
	spaceId int not null,
	code varchar(32) not null,
	description varchar(64) not null,
	units decimal(4,2) default 1,
	price decimal(8,2) not null,
	includedMemberships int not null default 0,
	additionalMemberships int not null default 0,
	requestable tinyint DEFAULT 1,
	sortOrder int not null default 0,
	PRIMARY KEY (id)
);

ALTER TABLE vendorSpacePrices add constraint vendorSpacePrices_space
	FOREIGN KEY (spaceId)
	REFERENCES vendorSpaces(id)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

CREATE TABLE vendor_space(
	id int NOT NULL AUTO_INCREMENT,
	conid int NOT NULL,
	vendorId int NOT NULL,
	spaceId int NOT NULL,
	item_requested int DEFAULT NULL,
	time_requested timestamp DEFAULT NULL,
	item_approved int DEFAULT NULL,
	time_approved timestamp DEFAULT NULL,
	item_purchased int DEFAULT NULL,
	time_purchased timestamp DEFAULT NULL,
	price DECIMAL(8,2) DEFAULT NULL,
	paid DECIMAL(8,2) DEFAULT NULL,
	transid int DEFAULT NULL,
	membershipCredits int DEFAULT 0,
	PRIMARY KEY (id)
);

ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_vendor
	FOREIGN KEY (vendorId)
	REFERENCES vendors(id)
	ON UPDATE CASCADE;

ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_space
	FOREIGN KEY (spaceId)
	REFERENCES vendorSpaces(id)
	ON UPDATE CASCADE;
    
ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_conid
	FOREIGN KEY (conid)
	REFERENCES conlist(id)
	ON UPDATE CASCADE;
    
ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_trans
	FOREIGN KEY (transid)
	REFERENCES transaction(id)
	ON UPDATE CASCADE;

ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_req
	FOREIGN KEY (item_requested)
	REFERENCES vendorSpacePrices(id)
	ON UPDATE CASCADE;

ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_app
	FOREIGN KEY (item_approved)
	REFERENCES vendorSpacePrices(id)
	ON UPDATE CASCADE;

ALTER TABLE vendor_space ADD CONSTRAINT vendor_space_pur
	FOREIGN KEY (item_purchased)
	REFERENCES vendorSpacePrices(id)
	ON UPDATE CASCADE;

CREATE OR REPLACE VIEW vw_VendorSpace AS
	SELECT v.id, v.conid, v.vendorId, v.spaceId, 
		vs.shortname, vs.name,
		req.id AS item_requested,
		v.time_requested,
		req.code AS requested_code,
		req.description AS requested_description,
		req.units AS requested_units,
		req.price AS requested_price,
		req.sortOrder AS requested_sort,
		app.id AS item_approved,
		v.time_approved,
		app.code AS approved_code,
		app.description AS approved_description,
		app.units AS approved_units,
		app.price AS approved_price,
		app.sortOrder AS approved_sort,
		pur.id AS item_purchased,
		v.time_purchased,
		pur.code AS purchased_code,
		pur.description AS purchased_description,
		pur.units AS purchased_units,
		pur.price AS purchased_price,
		pur.sortOrder AS purchased_sort,
		v.price,
		v.paid,
		v.transid,
		v.membershipCredits
	FROM vendor_space v
	JOIN vendorSpaces vs ON (vs.id = v.spaceId)
	LEFT OUTER JOIN vendorSpacePrices req ON (v.item_requested = req.id)
	LEFT OUTER JOIN vendorSpacePrices app ON (v.item_approved = app.id)
	LEFT OUTER JOIN vendorSpacePrices pur ON (v.item_purchased = pur.id);


ALTER TABLE payments modify column category enum('reg','artshow','other','vendor') DEFAULT NULL;

INSERT INTO patchLog(id, name) values(8, 'new vendor');

