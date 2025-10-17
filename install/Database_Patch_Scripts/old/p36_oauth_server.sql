/*
 * Copyright (c) 2024, Michael Rafferty
 * ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3. See the ConTroll™ ReadMe file.
 */

/*
 * add oauth2 server management tables
 */

/* Rerun drop table section

   DROP TABLE IF EXISTS oauthRefreshTokens;
   DROP TABLE IF EXISTS oauthAccessTokens;
   DROP TABLE IF EXISTS oauthAuthCodes;
   DROP TABLE IF EXISTS oauthClients;
   DROP TABLE IF EXISTS oauthScopes;
 */

-- Scopes Table
CREATE TABLE oauthScopes (
    id varchar(100) NOT NULL,
    description varchar(512) NULL,
    PRIMARY KEY (id)
);

CREATE TABLE oauthClients (
    clientId varchar(100) NOT NULL,
    clientSecret varchar(255) NOT NULL,
    redirectUri varchar(2048) NOT NULL,
    name varchar(255) NOT NULL,
    alloweGrantTypes varchar(512) NULL,
    PRIMARY KEY (clientId)
);

-- Authorization Codes Table
CREATE TABLE oauthAuthCodes (
    id varchar(100) NOT NULL,         -- This is the authorization code itself
    userId int NULL,
    clientId varchar(100) NOT NULL,
    scopes varchar(512) NULL,
    expiresAt datetime NOT NULL,
    revoked tinyint(1) NOT NULL DEFAULT 0,
    redirectUri varchar(2048) NULL,
    PRIMARY KEY (id)
);
ALTER TABLE oauthAuthCodes ADD CONSTRAINT fk_auth_codes_clients
    FOREIGN KEY (clientId) REFERENCES oauthClients(clientId) ON UPDATE CASCADE;
CREATE INDEX idx_oauth_auth_codes_user ON oauthAuthCodes(userId);
CREATE INDEX idx_oauth_auth_codes_client ON oauthAuthCodes(clientId);


-- Access Tokens Table
CREATE TABLE oauthAccessTokens (
    id varchar(100) NOT NULL,         -- This is the access token string
    userId int NULL,
    clientId varchar(100) NOT NULL,
    scopes varchar(512) NULL,
    expiresAt datetime NOT NULL,
    revoked tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
ALTER TABLE oauthAccessTokens ADD CONSTRAINT fk_access_tokens_clients
    FOREIGN KEY (clientId) REFERENCES oauthClients(clientId) ON UPDATE CASCADE;
CREATE INDEX idx_oauth_access_tokens_user ON oauthAccessTokens(userId);
CREATE INDEX idx_oauth_access_tokens_client ON oauthAccessTokens(clientId);

-- Refresh Tokens Table
CREATE TABLE oauthRefreshTokens (
    id varchar(100) NOT NULL,         -- This is the refresh token string
    accessTokenId varchar(100) NOT NULL,
    expiresAt datetime NOT NULL,
    revoked tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
ALTER TABLE oauthRefreshTokens ADD CONSTRAINT fk_refresh_token_access
    FOREIGN KEY (accessTokenId) REFERENCES oauthAccessTokens(id) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) VALUES(36, 'oauth2_server');

