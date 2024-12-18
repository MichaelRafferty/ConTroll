/*
 * Copyright (c) 2024, Michael Rafferty
 * ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3. See the ConTroll™ ReadMe file.
 */

/*
 * add oauth2 server management tables
 */

/* questions for chris -
        why is this not key'd by client_id vs just id
        why is allowed_grant_types a text field and not a varchar field (issue with orphan data spaces), (should this be a coalesce off a grant type table tie
        why is scopes a text field and not a varchar field (issue with orphan data spaces), (should this be a coalesce off a table ref'ing the scopes table
            tie
        would like a create date and created by
        will need a screen to add these to the system in 'admin' page
        why is redirect a text and not a varchar (same orphan data issue)
        what is user_id, does it tie to anywhere
 */
/* Rerun drop table section

   DROP TABLE IF EXISTS oauth_refresh_tokens;
   DROP TABLE IF EXISTS oauth_access_tokens;
   DROP TABLE IF EXISTS oauth_auth_codes;
   DROP TABLE IF EXISTS oauth_clients;
   DROP TABLE IF EXISTS oauth_scopes;
 */

-- Scopes Table
CREATE TABLE oauth_scopes (
    id VARCHAR(100) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE oauth_clients (
    id INT AUTO_INCREMENT NOT NULL,
    client_id VARCHAR(100) NOT NULL UNIQUE,
    client_secret VARCHAR(255) NOT NULL,
    redirect_uri TEXT NOT NULL,
    name VARCHAR(255) NOT NULL,
    allowed_grant_types TEXT NULL,
    PRIMARY KEY (id)
);

-- Authorization Codes Table
CREATE TABLE oauth_auth_codes (
    id VARCHAR(100) NOT NULL,         -- This is the authorization code itself
    user_id INT NULL,
    client_id VARCHAR(100) NOT NULL,
    scopes TEXT NULL,
    expires_at DATETIME NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    redirect_uri TEXT NULL,
    PRIMARY KEY (id)
);
ALTER TABLE oauth_auth_codes ADD CONSTRAINT fk_auth_codes_clients
    FOREIGN KEY (client_id) REFERENCES oauth_clients(client_id) ON UPDATE CASCADE;
CREATE INDEX idx_oauth_auth_codes_user ON oauth_auth_codes(user_id);
CREATE INDEX idx_oauth_auth_codes_client ON oauth_auth_codes(client_id);


-- Access Tokens Table
CREATE TABLE oauth_access_tokens (
    id VARCHAR(100) NOT NULL,         -- This is the access token string
    user_id INT NULL,
    client_id VARCHAR(100) NOT NULL,
    scopes TEXT NULL,
    expires_at DATETIME NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
ALTER TABLE oauth_access_tokens ADD CONSTRAINT fk_access_tokens_clients
    FOREIGN KEY (client_id) REFERENCES oauth_clients(client_id) ON UPDATE CASCADE;
CREATE INDEX idx_oauth_access_tokens_user ON oauth_access_tokens(user_id);
CREATE INDEX idx_oauth_access_tokens_client ON oauth_access_tokens(client_id);

-- Refresh Tokens Table
CREATE TABLE oauth_refresh_tokens (
    id VARCHAR(100) NOT NULL,         -- This is the refresh token string
    access_token_id VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
ALTER TABLE oauth_refresh_tokens ADD CONSTRAINT fk_refresh_tokens_access_tokens
    FOREIGN KEY (access_token_id) REFERENCES oauth_access_tokens(id) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) VALUES(36, 'oauth2_server');