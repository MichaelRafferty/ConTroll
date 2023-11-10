CREATE TABLE patchLog (
	id int not null,
	name varchar(256),
	installDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
);
