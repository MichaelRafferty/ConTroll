-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `policies`
--

LOCK TABLES `policies` WRITE;
ALTER TABLE `policies` DISABLE KEYS;
INSERT INTO policies(policy,prompt,description,required,defaultValue,active,sortOrder) VALUES
('conduct','Do you agree to the code of conduct as listed at&nbsp;<a href="#POLICYLINK#" target="_blank">#POLICYTEXT#</a>?','You must agree to the code of
# conduct to continue. Please make sure you have read and understand it before answering.','Y','N','Y',10),
('marketing','Please send me the newsletter and other announcements. If you uncheck this box, you will receive only essential communications such as receipts
and voting information.','#CONLABEL# will never sell or give away your information to third parties except as you authorize. If you uncheck this box we will
not be able to send you information related to the convention and its activities.','N','Y','N',20),
('pass-on','Please pass my information to the XXXX Worldcon so I can nominate for the Hugos in XXXX.','Anyone who is a WSFS member of #CONLABEL# is able to
nominate for the Hugo awards in XXXX if their information is passed along to that Worldcon.','N','Y','N',30),
('website','Please list my name on the #CONLABEL# website.','Your badge name will appear as entered above, or in the form First Name + Last Name.','N','N','N',40);
ALTER TABLE `policies` ENABLE KEYS;
UNLOCK TABLES;
