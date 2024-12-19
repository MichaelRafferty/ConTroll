<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use function array_key_exists;
use function password_hash;
use function password_verify;

class ClientRepository implements ClientRepositoryInterface
{
  private const CLIENT_NAME = 'pyramid';
  private const REDIRECT_URI = 'http://www.example.com/';

  /**
   * {@inheritdoc}
   */
  public function getClientEntity($clientIdentifier): ?ClientEntityInterface
  {
    $client = new ClientEntity();

    $client->setIdentifier($clientIdentifier);
    $client->setName(self::CLIENT_NAME);
    $client->setRedirectUri(self::REDIRECT_URI);
    $client->setConfidential();

    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
  {
    $clients = [
      'pyramid' => [
        'secret' => password_hash('obelisk', PASSWORD_BCRYPT),
        'name' => self::CLIENT_NAME,
        'redirect_uri' => self::REDIRECT_URI,
        'is_confidential' => true,
      ],
    ];

    // Check if client is registered
    if (array_key_exists($clientIdentifier, $clients) === false) {
      return false;
    }

    if (password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false) {
      return false;
    }

    return true;
  }
}
