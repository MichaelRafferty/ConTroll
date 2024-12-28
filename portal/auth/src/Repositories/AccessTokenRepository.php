<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
  {
    // Some logic here to save the access token to a database
    //
    // Insert into DB: token_id, expiration, user_id, client_id, scopes
  }

  /**
   * {@inheritdoc}
   */
  public function revokeAccessToken($tokenId): void
  {
    // Update DB: token with $tokenId set revoked = true
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessTokenRevoked($tokenId): bool
  {
    // TODO: Check DB for $tokenId revoked and return it

    return false; // Access token hasn't been revoked
  }

  /**
   * {@inheritdoc}
   */
  public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
  {
    $accessToken = new AccessTokenEntity();

    $accessToken->setClient($clientEntity);

    foreach ($scopes as $scope) {
      $accessToken->addScope($scope);
    }

    if ($userIdentifier !== null) {
      $accessToken->setUserIdentifier((string)$userIdentifier);
    }

    return $accessToken;
  }
}
