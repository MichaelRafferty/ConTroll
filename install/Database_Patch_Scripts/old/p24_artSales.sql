/*
 * changes needed to make sales work and track the things we want to track
 */
ALTER TABLE artItems MODIFY COLUMN `status`     enum('Entered','Not In Show','Checked In','NFS','BID','Quicksale/Sold','Removed from Show',  'purchased/released','To Auction','Sold Bid Sheet','Sold At Auction', 'Checked Out') DEFAULT 'Entered';

INSERT INTO patchLog(id, name) values(24, 'artsales status');
