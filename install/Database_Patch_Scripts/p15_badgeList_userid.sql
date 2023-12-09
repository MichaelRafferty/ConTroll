/* P15 - badgeList - convert from userid to perid mapping as part of userid phase out */

ALTER TABLE badgeList drop FOREIGN KEY `badgeList_userid_fk`;

ALTER TABLE badgeList rename column userid TO user_perid;

UPDATE badgeList
JOIN user ON (user.id = badgeList.user_perid)
SET user_perid = user.perid;

ALTER TABLE badgeList
ADD CONSTRAINT `badgeList_user_perid_fk` FOREIGN KEY (`user_perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) values(14, 'mergePerid_proc');
