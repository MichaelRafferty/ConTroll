<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\UserEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function getUserEntityByUserCredentials(
    $username,
    $password,
    $grantType,
    ClientEntityInterface $clientEntity
  ): ?UserEntityInterface
  {
    // Check user database for username and validate against password

    if ($username === 'thoth' && $password === 'knowledge') {
      $userEntity = new UserEntity();

      $userEntity->setIdentifier(1 /* $user['id'] */);

      return $userEntity;
    }

    return null;
  }
}
