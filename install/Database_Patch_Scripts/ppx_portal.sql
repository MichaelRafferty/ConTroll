/*
 * changes needed to make sales work and track the things we want to track
 */
CREATE TABLE portalTokenLinks (
    id int NOT NULL AUTO_INCREMENT,
    email varchar(254) NOT NULL,
    source_ip varchar(16) NOT NULL,
    createdTS timestamp NOT NULL default NOW(),
    useCnt int NOT NULL DEFAULT 0,
    useIP varchar(16) DEFAULT NULL,
    useTS timestamp DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE INDEX ptlEmail_idx ON portalTokenLinks (email ASC, createdTS DESC);

INSERT INTO patchLog(id, name) values(ppx, 'Portal Changes');
