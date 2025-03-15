-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `controllTxtItems`
--

LOCK TABLES `controllTxtItems` WRITE;
ALTER TABLE `controllTxtItems` DISABLE KEYS;
INSERT INTO `controllTxtItems` VALUES
('exhibitor','index','invoice','afterPrice','<p>Please fill out this section with information on the <<portalType>> or store.</p>'),
('portal','accountSettings','main','identities','<p>If you have multiple email addresses, and do not want to have to remember which one you used to create your account, you may add the others here to link them. You may also delete any accounts you no longer use.<br><br>There are two ways to login to the registration portal.<br>If you wish to login using the \"Email with Authentication\" method, leave the provider space blank. You will receive an email with a link to login and confirm.<br>If you wish to login using Google, type \"google\" in the provider space. You will receive an email now to login and confirm, but in the future can use your Google account to login</p>'),
('portal','addUpgrade','main','step1','<p>The purchase price of a membership is determined in part by the age of the member.  That is why we need this information.</p>\n<p>Children under the age of 13 must be associated with an adult or young adult member of the convention in order to create an attending membership.\nAn attending membership for them cannot be created until there is a membership for a person of guardianship age either purchased or in the cart.</p>'),
('portal','addUpgrade','main','step4','<p>This is where you may purchase memberships,&nbsp; upgrade your current membership and make donations and and any other special Membership offerings.</p>\n<p>Not to buy a Child or Kid In Tow membership you must have an over 18 membership already in your account.</p>'),
('portal','index','main','notloggedin','<p>\nIf you don’t already have an account, the next screen will say: \n“The email (your email address) does not have an account” and we will take you through the steps of creating an account.\n</p>\n<p>This is our way of asking “Are you Sure this is the correct email?”  If you’re sure then go ahead and create your account.</p>\n<p>You will still need to create a minimal account for yourself even if you are just buying a membership for someone else if you want to receive a valid receipt for your payment.</p>'),
('portal','portal','main','changeEmail','<p>You can only change your accounts email address to an email address in your identities in Account Settings.  Please use the \"Add New\" button to add any new email addresses to your account. Identities is only available in Account Settings once your account has been assigned an ID and is no longer pending.</p>\n<p>You can only change the email address for an account you manage to one of your own (as above) or to one of the email addresses of people you manage.</p>\n<p>If you need to make any other changes, please contact registration and ask for assistance</p>'),
('portal','portal','main','purchased','<p>Please review our payment plan policy. <<PaymentPlanPolicy>></p>\n<p>The minimum payment amount for any payment is <<MinPayment>>.</p>'),
ALTER TABLE `controllTxtItems` ENABLE KEYS;
