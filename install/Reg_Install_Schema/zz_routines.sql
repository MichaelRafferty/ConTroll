-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Final view structure for view `vw_ExhibitorSpace`
--

DROP VIEW IF EXISTS `vw_ExhibitorSpace`;
CREATE ALGORITHM=UNDEFINED 
SQL SECURITY INVOKER
VIEW `vw_ExhibitorSpace` AS select `ert`.`portalType` AS `portalType`,`ert`.`requestApprovalRequired` AS `requestApprovalRequired`,`ert`.`purchaseApprovalRequired` AS `purchaseApprovalRequired`,`ert`.`purchaseAreaTotals` AS `purchaseAreaTotals`,`ert`.`mailinAllowed` AS `mailInAllowed`,`er`.`name` AS `regionName`,`er`.`shortname` AS `regionShortName`,`er`.`description` AS `regionDesc`,`er`.`sortorder` AS `regionSortOrder`,`ery`.`ownerName` AS `ownerName`,`ery`.`ownerEmail` AS `ownerEmail`,`ery`.`id` AS `regionYearId`,`ery`.`includedMemId` AS `includedMemId`,`ery`.`additionalMemId` AS `additionalMemId`,`ery`.`totalUnitsAvailable` AS `totalUnitsAvailable`,`ery`.`conid` AS `yearId`,`s`.`id` AS `id`,`Ey`.`conid` AS `conid`,`e`.`id` AS `exhibitorId`,`s`.`spaceId` AS `spaceId`,`es`.`shortname` AS `shortname`,`es`.`name` AS `name`,`s`.`item_requested` AS `item_requested`,`s`.`time_requested` AS `time_requested`,`req`.`code` AS `requested_code`,`req`.`description` AS `requested_description`,`req`.`units` AS `requested_units`,`req`.`price` AS `requested_price`,`req`.`sortorder` AS `requested_sort`,`s`.`item_approved` AS `item_approved`,`s`.`time_approved` AS `time_approved`,`app`.`code` AS `approved_code`,`app`.`description` AS `approved_description`,`app`.`units` AS `approved_units`,`app`.`price` AS `approved_price`,`app`.`sortorder` AS `approved_sort`,`s`.`item_purchased` AS `item_purchased`,`s`.`time_purchased` AS `time_purchased`,`pur`.`code` AS `purchased_code`,`pur`.`description` AS `purchased_description`,`pur`.`units` AS `purchased_units`,`pur`.`price` AS `purchased_price`,`pur`.`sortorder` AS `purchased_sort`,`s`.`price` AS `price`,`s`.`paid` AS `paid`,`s`.`transid` AS `transid`,`s`.`membershipCredits` AS `membershipCredits` from (((((((((`exhibitors` `e` join `exhibitorYears` `Ey` on((`e`.`id` = `Ey`.`exhibitorId`))) left join `exhibitorSpaces` `s` on((`Ey`.`id` = `s`.`exhibitorYearId`))) left join `exhibitsSpacePrices` `req` on((`s`.`item_requested` = `req`.`id`))) left join `exhibitsSpacePrices` `app` on((`s`.`item_approved` = `app`.`id`))) left join `exhibitsSpacePrices` `pur` on((`s`.`item_purchased` = `pur`.`id`))) left join `exhibitsSpaces` `es` on((`s`.`spaceId` = `es`.`id`))) join `exhibitsRegionYears` `ery` on((`es`.`exhibitsRegionYear` = `ery`.`id`))) join `exhibitsRegions` `er` on((`er`.`id` = `ery`.`exhibitsRegion`))) join `exhibitsRegionTypes` `ert` on((`ert`.`regionType` = `er`.`regionType`))) ;

--
-- Final view structure for view `memLabel`
--

DROP VIEW IF EXISTS `memLabel`;
CREATE ALGORITHM=UNDEFINED 
SQL SECURITY INVOKER
VIEW `memLabel` AS select `m`.`id` AS `id`,`m`.`conid` AS `conid`,`m`.`sort_order` AS `sort_order`,`m`.`memCategory` AS `memCategory`,`m`.`memType` AS `memType`,`m`.`memAge` AS `memAge`,`m`.`label` AS `shortname`,concat(`m`.`label`,' [',`a`.`label`,']') AS `label`,concat(`m`.`memCategory`,'_',`m`.`memType`,'_',`m`.`memAge`) AS `memGroup`,`m`.`price` AS `price`,`m`.`startdate` AS `startdate`,`m`.`enddate` AS `enddate`,`m`.`atcon` AS `atcon`,`m`.`online` AS `online` from (`memList` `m` join `ageList` `a` on(((`m`.`memAge` = `a`.`ageType`) and (`m`.`conid` = `a`.`conid`)))) ;

--
-- Final view structure for view `couponUsage`
--

DROP VIEW IF EXISTS `couponUsage`;
CREATE ALGORITHM=UNDEFINED 
SQL SECURITY INVOKER
VIEW `couponUsage` AS select `t`.`conid` AS `conid`,`t`.`id` AS `transId`,`c`.`id` AS `CouponId`,`t`.`perid` AS `perid`,`t`.`price` AS `price`,`t`.`couponDiscount` AS `couponDiscount`,`t`.`paid` AS `paid`,`c`.`code` AS `code`,`c`.`name` AS `name`,`c`.`couponType` AS `couponType`,`c`.`discount` AS `discount`,`c`.`oneUse` AS `oneUse`,`k`.`guid` AS `guid`,`k`.`useTS` AS `useTS` from ((`transaction` `t` join `coupon` `c` on((`c`.`id` = `t`.`coupon`))) left join `couponKeys` `k` on((`k`.`usedBy` = `t`.`id`))) ;

--
-- Final view structure for view `couponMemberships`
--

DROP VIEW IF EXISTS `couponMemberships`;
CREATE ALGORITHM=UNDEFINED 
SQL SECURITY INVOKER
VIEW `couponMemberships` AS select `r`.`id` AS `regId`,`r`.`conid` AS `conid`,`r`.`perid` AS `perid`,`r`.`price` AS `price`,`r`.`couponDiscount` AS `couponDiscount`,`r`.`paid` AS `paid`,`c`.`id` AS `couponId`,`c`.`code` AS `code`,`c`.`name` AS `name`,`c`.`couponType` AS `couponType`,`c`.`discount` AS `discount`,`c`.`oneUse` AS `oneUse`,`k`.`guid` AS `guid`,`k`.`useTS` AS `useTS` from ((`reg` `r` join `coupon` `c` on((`c`.`id` = `r`.`coupon`))) left join `couponKeys` `k` on((`k`.`usedBy` = `r`.`create_trans`))) ;


-- Dump completed on 2024-02-08 13:02:00
