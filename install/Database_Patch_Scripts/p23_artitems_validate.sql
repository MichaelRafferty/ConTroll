/* p23 - artitems_validate- add Entered status to allow for edit validations
 */

ALTER TABLE artItems MODIFY COLUMN `status`     enum('Entered','Not In Show','Checked In','NFS','BID','Quicksale/Sold','Removed from Show',
        'purchased/released','To Auction','Sold Bid Sheet','Checked Out') DEFAULT NULL;

UPDATE artItems SET status = 'Entered' where status is NULL;

ALTER TABLE artItems MODIFY COLUMN `status`     enum('Entered','Not In Show','Checked In','NFS','BID','Quicksale/Sold','Removed from Show',
    'purchased/released','To Auction','Sold Bid Sheet','Checked Out') DEFAULT 'Entered';

INSERT INTO patchLog(id, name) values(23, 'artitems_validate');
