ALTER TABLE exhibitorRegionYears ADD CONSTRAINT `ecry_updateby_fk` FOREIGN KEY (`updateBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorRegionYears ADD CONSTRAINT `exry_agentNewperon` FOREIGN KEY (`agentNewperson`) REFERENCES `newperson` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorRegionYears ADD CONSTRAINT `exry_agentPerid` FOREIGN KEY (`agentPerid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorRegionYears ADD CONSTRAINT `exry_eyid` FOREIGN KEY (`exhibitorYearId`) REFERENCES `exhibitorYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorRegionYears ADD CONSTRAINT `exry_eyrid` FOREIGN KEY (`exhibitsRegionYearId`) REFERENCES `exhibitsRegionYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_exRY_fk` FOREIGN KEY (`exhibitorRegionYear`) REFERENCES `exhibitorRegionYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_app_fk` FOREIGN KEY (`item_approved`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_pur_fk` FOREIGN KEY (`item_purchased`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_req_fk` FOREIGN KEY (`item_requested`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_spaceid_fk` FOREIGN KEY (`spaceId`) REFERENCES `exhibitsSpaces` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_transaction_fk` FOREIGN KEY (`transid`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE artshow_reg ADD CONSTRAINT `artshow_reg_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE artshow_reg ADD CONSTRAINT `conid_fkey` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`);
ALTER TABLE reg ADD CONSTRAINT `reg_complete_fk` FOREIGN KEY (`complete_trans`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_coupon_fk` FOREIGN KEY (`coupon`) REFERENCES `coupon` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_create_trans_fk` FOREIGN KEY (`create_trans`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_memId_fk` FOREIGN KEY (`memId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_newperid_fk` FOREIGN KEY (`newperid`) REFERENCES `newperson` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg ADD CONSTRAINT `reg_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE user ADD CONSTRAINT `fk_user_perid` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`);
ALTER TABLE payments ADD CONSTRAINT `payments_cashier_fk` FOREIGN KEY (`cashier`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE payments ADD CONSTRAINT `payments_transid_fk` FOREIGN KEY (`transid`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE payments ADD CONSTRAINT `payments_userid_fk` FOREIGN KEY (`userid`) REFERENCES `user` (`id`) ON UPDATE CASCADE;
ALTER TABLE ageList ADD CONSTRAINT `ageList_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegions ADD CONSTRAINT `er_regiontype_fk` FOREIGN KEY (`regionType`) REFERENCES `exhibitsRegionTypes` (`regionType`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitors ADD CONSTRAINT `exhibitor_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitors ADD CONSTRAINT `exhibitors_newperson_fk` FOREIGN KEY (`newperid`) REFERENCES `newperson` (`id`) ON UPDATE CASCADE;
ALTER TABLE newperson ADD CONSTRAINT `newperson_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE newperson ADD CONSTRAINT `newperson_transid_fk` FOREIGN KEY (`transid`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE atcon_user ADD CONSTRAINT `atcon_user_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE atcon_user ADD CONSTRAINT `atcon_user_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitsSpaces ADD CONSTRAINT `es_exhibitsRegionYears_fk` FOREIGN KEY (`exhibitsRegionYear`) REFERENCES `exhibitsRegionYears` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitsSpacePrices ADD CONSTRAINT `esp_exhibitsspaceid_fk` FOREIGN KEY (`spaceId`) REFERENCES `exhibitsSpaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE user_auth ADD CONSTRAINT `user_auth_auth_id_fk` FOREIGN KEY (`auth_id`) REFERENCES `auth` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE user_auth ADD CONSTRAINT `user_auth_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT `memList_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT `memList_memAge_fk` FOREIGN KEY (`conid`, `memAge`) REFERENCES `ageList` (`conid`, `ageType`) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT `memList_memCategory_fk` FOREIGN KEY (`memCategory`) REFERENCES `memCategories` (`memCategory`) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT `memList_memType_fk` FOREIGN KEY (`memType`) REFERENCES `memTypes` (`memType`) ON UPDATE CASCADE;
ALTER TABLE coupon ADD CONSTRAINT `coupon_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE coupon ADD CONSTRAINT `coupon_createby_fk` FOREIGN KEY (`createBy`) REFERENCES `user` (`id`) ON UPDATE CASCADE;
ALTER TABLE coupon ADD CONSTRAINT `coupon_memid_fk` FOREIGN KEY (`memId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;
ALTER TABLE coupon ADD CONSTRAINT `coupon_updateby_fk` FOREIGN KEY (`updateBy`) REFERENCES `user` (`id`) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT `artshow_agent_fk` FOREIGN KEY (`agent`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT `artshow_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE artshow ADD CONSTRAINT `artshow_perinfo_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorYears ADD CONSTRAINT `ey_conlist_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorYears ADD CONSTRAINT `ey_exhibitors_fk` FOREIGN KEY (`exhibitorId`) REFERENCES `exhibitors` (`id`) ON UPDATE CASCADE;
ALTER TABLE transaction ADD CONSTRAINT `transaction_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE transaction ADD CONSTRAINT `transaction_newperid_fk` FOREIGN KEY (`newperid`) REFERENCES `newperson` (`id`) ON UPDATE CASCADE;
ALTER TABLE transaction ADD CONSTRAINT `transaction_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE badgeList ADD CONSTRAINT `badgeList_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE badgeList ADD CONSTRAINT `badgeList_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE badgeList ADD CONSTRAINT `badgeList_user_perid_fk` FOREIGN KEY (`user_perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE club ADD CONSTRAINT `psfs_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE atcon_auth ADD CONSTRAINT `atcon_auth_user` FOREIGN KEY (`authuser`) REFERENCES `atcon_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE atcon_auth ADD CONSTRAINT `atcon_authuser_fk` FOREIGN KEY (`authuser`) REFERENCES `atcon_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE couponKeys ADD CONSTRAINT `couponkey_usedby_fk` FOREIGN KEY (`usedBy`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE couponKeys ADD CONSTRAINT `couponkeys_couponid_fk` FOREIGN KEY (`couponId`) REFERENCES `coupon` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE couponKeys ADD CONSTRAINT `couponkeys_createby_fk` FOREIGN KEY (`createBy`) REFERENCES `user` (`id`) ON UPDATE CASCADE;
ALTER TABLE couponKeys ADD CONSTRAINT `couponkeys_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE artsales ADD CONSTRAINT `artsales_artitem_fk` FOREIGN KEY (`artid`) REFERENCES `artItems` (`id`) ON UPDATE CASCADE;
ALTER TABLE artsales ADD CONSTRAINT `artsales_perinfo_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE artsales ADD CONSTRAINT `artsales_transid_fk` FOREIGN KEY (`transid`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE printers ADD CONSTRAINT `printers_server` FOREIGN KEY (`serverName`) REFERENCES `servers` (`serverName`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_conlist_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_exhibitsRegion_fk` FOREIGN KEY (`exhibitsRegion`) REFERENCES `exhibitsRegions` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_memList_a` FOREIGN KEY (`additionalMemId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_memList_i` FOREIGN KEY (`includedMemId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItems ADD CONSTRAINT `artItems_artshow_fk` FOREIGN KEY (`artshow`) REFERENCES `artshow` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItems ADD CONSTRAINT `artItems_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItems ADD CONSTRAINT `artItems_exhibitorRegionYear_fk` FOREIGN KEY (`exhibitorRegionYearId`) REFERENCES `exhibitorRegionYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE reg_history ADD CONSTRAINT `atcon_history_regid_fk` FOREIGN KEY (`regid`) REFERENCES `reg` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE reg_history ADD CONSTRAINT `atcon_history_tid_fk` FOREIGN KEY (`tid`) REFERENCES `transaction` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE reg_history ADD CONSTRAINT `atcon_history_userid_fk` FOREIGN KEY (`userid`) REFERENCES `perinfo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
