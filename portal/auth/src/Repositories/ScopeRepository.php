<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use function array_key_exists;

class ScopeRepository implements ScopeRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function getScopeEntityByIdentifier($inputIdentifier): ?ScopeEntityInterface
  {
    $scopes = [
      'basic' => [
        'description' => 'Basic member details',
      ],
      'email' => [
        'description' => 'Member email address',
      ],
    ];

    if (array_key_exists($inputIdentifier, $scopes) === false) {
      return null;
    }

    $scope = new ScopeEntity();
    $scope->setIdentifier($inputIdentifier);

    return $scope;
  }

  /**
   * {@inheritdoc}
   */
  public function finalizeScopes(
    array                 $scopes,
                          $grantType,
    ClientEntityInterface $clientEntity,
                          $userIdentifier = null,
                          $authCodeId = null
  ): array
  {
    // Example of programatically modifying the final scope of the access token

    // if ((int)$userIdentifier === 1) {
    //   $scope = new ScopeEntity();
    //   $scope->setIdentifier('email');
    //   $scopes[] = $scope;
    // }

    return $scopes;
  }
}
