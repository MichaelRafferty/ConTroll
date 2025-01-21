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
  private const CLIENT_NAME = 'nomnom';

  private const REDIRECT_URIS = [
    'https://nomnom-staging.seattlein2025.org/complete/controll/',
    'https://void.camel-tortoise.ts.net/complete/controll/',
    'https://nomnom.seattlein2025.org/complete/controll/'
  ];

  /**
   * {@inheritdoc}
   */
  public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        $client = new ClientEntity();

        $client->setIdentifier($clientIdentifier);
        $client->setName(self::CLIENT_NAME);

        // Optionally pick the first URI for `setRedirectUri` if needed (for compatibility).
        $client->setRedirectUris(self::REDIRECT_URIS);

        $client->setConfidential();

        return $client;
    }

  /**
   * {@inheritdoc}
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
  {
    $clients = [
      'nomnom' => [
        'secret' => password_hash('nominateme', PASSWORD_BCRYPT),
        'name' => self::CLIENT_NAME,
        'redirect_uris' => self::REDIRECT_URIS,
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
