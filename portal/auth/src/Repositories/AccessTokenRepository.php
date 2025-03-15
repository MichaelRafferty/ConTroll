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
    // Save the access token to a database

        $data = [
            'user_id' => $accessTokenEntity->getUserIdentifier(), // User ID
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(), // Client ID
            'scopes' => json_encode($accessTokenEntity->getScopes()), // Scopes as JSON
            'expiry' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'), // Expiry DateTime
            'token_id' => $accessTokenEntity->getIdentifier(), // Unique token identifier
        ];

        //file_put_contents(
        //    __DIR__ . '/tokendebug.log',
        //    "Persisting Access Token: " . print_r($accessTokenEntity, true) . "\n" .
        //    "Persisting Data: " . print_r($data, true) . "\n",
        //    FILE_APPEND
        //);

    // Save to database
    //saveAccessTokenToDatabase($data);
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

    //    file_put_contents(
    //        __DIR__ . '/newtokendebug.log',
    //        "Data: " . print_r($userIdentifier, true) . "\n",
    //        FILE_APPEND
    //    );

    return $accessToken;
  }
}
