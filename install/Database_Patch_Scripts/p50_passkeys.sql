/* P50
 * addition of passkeys
 */

DROP TABLE IF EXISTS passkeys;
CREATE TABLE passkeys(
    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    credentialId varchar(1023) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL COMMENT 'Received from the authentication device',
    relyingParty varchar(255) NOT NULL COMMENT 'Set in the reg_admin.ini file as how many parts of the hostname (R-L)',
    source varchar(32) DEFAULT NULL COMMENT 'Which application created this entry',
    userId varchar(64) NOT NULL COMMENT 'sha256 of the email address (hex string)',
    userName varchar(255) NOT NULL COMMENT 'This is the email address again, but stored separately in case we want to change the userId',
    userDisplayName varchar(255) NOT NULL COMMENT 'This is a friendly name the user chooses when registering the passkey',
    createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createIP varchar(32) DEFAULT NULL,
    lastUsedDate datetime DEFAULT NULL,
    lastUsedIP varchar(32) DEFAULT NULL,
    useCount int NOT NULL DEFAULT 0,
    publicKey varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE UNIQUE INDEX passkeys_cid_idx ON passkeys(credentialId);
CREATE INDEX passkeys_uid_idx ON passkeys(userId);
CREATE INDEX passkeys_uname_idx ON passkeys(userName);

INSERT INTO `controllAppItems` VALUES
('portal','accountSettings','main','passkeys','Just after the passkeys header');

INSERT INTO `controllTxtItems` VALUES
('portal','accountSettings','main','passkeys',
    CONCAT('<p>A quicker and more secure way to login into your account is by using a passkey.</p>',
    '<p>Passkeys are a public-key encryption system to securely identify you and require some sort of identification before they can be used. ',
    'Passkeys can be held in your browser, synced to the cloud, or stored in your password manager.</p>',
    '<p>Passkeys are an immediate form of login. ',
    'No waiting for a token to arrive in the email or a 2FA verification code to be sent to your phone or email account.</p>',
    '<p>In this section you can create a new passkey, or delete an existing one.  You may have as many different passkeys as you wish, but each will be
you</p>'));

UPDATE controllTxtItems
SET contents = REPLACE(contents,
    'There are two ways to login to the registration portal:<br>',
    CONCAT('There are three ways to login to the registration portal: ',
           'you can use a passkey as described above, a link sent to you via email, ',
           'or a login with provider such as Google.<br/>'))
WHERE appName = 'portal' AND appPage = 'accountSettings' AND appSection = 'main' AND txtItem = 'identities';

/*
 * piggybacking in a vendor change for when vendors have to re-confirm their profile
 */

ALTER TABLE exhibitors DROP COLUMN confirm;
ALTER TABLE exhibitorYears DROP COLUMN confirm;
ALTER TABLE exhibitorYears DROP COLUMN needReview;
ALTER TABLE exhibitorYears ADD COLUMN lastVerified datetime DEFAULT current_timestamp NOT NULL AFTER need_new;


INSERT INTO patchLog(id, name) VALUES(xx, 'passkeys');