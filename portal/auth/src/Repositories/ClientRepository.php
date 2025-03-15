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
  /**
   * {@inheritdoc}
   */
  public function getClientEntity($clientIdentifier): ?ClientEntityInterface
  {
    $client = new ClientEntity();

    $client->setIdentifier($clientIdentifier);
    $client->setName($clientIdentifier);

    switch ($clientIdentifier) {

      case 'nomnom' :

        $client->setRedirectUris([
          'https://nomnom-staging.seattlein2025.org/complete/controll/',
          'https://void.camel-tortoise.ts.net/complete/controll/',
          'https://nomnom.seattlein2025.org/complete/controll/'
        ]);

        break;

      case 'authorama' :

        $client->setRedirectUris([
          'https://www.example.com/'
        ]);

        break;
    }


    $client->setConfidential();

    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
  {
    return true;

    $clients = [
      'nomnom' => [
        'secret' => password_hash('nominateme', PASSWORD_BCRYPT),
        'name' => 'nomnom',
        'redirect_uris' => [
          'https://nomnom-staging.seattlein2025.org/complete/controll/',
          'https://void.camel-tortoise.ts.net/complete/controll/',
          'https://nomnom.seattlein2025.org/complete/controll/',
        ],
        'is_confidential' => true,
      ],
      'authorama' => [
        'secret' => password_hash('ramaLlama33', PASSWORD_BCRYPT),
        'name' => 'authorama',
        'redirect_uris' => [
          'https://www.example.com/'
        ],
        'is_confidential' => true,
      ],
    ];

    if (array_key_exists($clientIdentifier, $clients) === false) {
      return false;
    }

    if (password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false) {
      return false;
    }

    return true;
  }
}
