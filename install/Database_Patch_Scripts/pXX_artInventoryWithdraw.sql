/*
 * pXX sets up artInventory to withdraw items from show.
 */

ALTER TABLE `artItems` CHANGE `status` `status`
    ENUM('Entered','Withdrawn','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_nopad_ci NULL DEFAULT 'Entered';

ALTER TABLE `artItemsHistory` CHANGE `status` `status`
    ENUM('Entered','Withdrawn','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

update artItemsHistory set status='Withdrawn' where status='Not In Show'

ALTER TABLE `artItemsHistory` CHANGE `status` `status`
    ENUM('Entered','Withdrawn','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;