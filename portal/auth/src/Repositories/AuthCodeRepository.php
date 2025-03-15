<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
  {
    // Insert into DB
    //
    // TODO: THIS IS EXAMPLE CODE!

    /*

    $this->db->execute('INSERT INTO oauth_auth_codes (id, client_id, user_id, scopes, expires_at, revoked, redirect_uri) VALUES (?, ?, ?, ?, ?, ?, ?)', [
      $authCodeEntity->getIdentifier(),
      $authCodeEntity->getClient()->getIdentifier(),
      $authCodeEntity->getUserIdentifier(),
      serialize($authCodeEntity->getScopes()),
      $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
      0,
      $authCodeEntity->getRedirectUri(),
    ]); */
  }

  /**
   * {@inheritdoc}
   */
  public function revokeAuthCode($codeId): void
  {
    // Update DB: set revoked = true

    // $this->db->execute('UPDATE oauth_auth_codes SET revoked = 1 WHERE id = ?', [$codeId]);
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthCodeRevoked($codeId): bool
  {
    return false; // The auth code has not been revoked

    // Query the database to check if the code is marked as revoked or doesn't exist.

    // TODO: EXAMPLE CODE. Craft this properly!
/*
    $record = $this->db->query('SELECT * FROM oauth_auth_codes WHERE id = ?', [$codeId]);

    if (!$record) {

      // Code doesn't exist, treat as revoked or invalid

      return true;
    }

    // If there's a 'revoked' column or 'used' column:

    return (bool)$record['revoked']; */
  }

  /**
   * {@inheritdoc}
   */
  public function getNewAuthCode(): AuthCodeEntityInterface|AuthCodeEntity
  {
    return new AuthCodeEntity();
  }
}
