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

    // TODO: return null if username/password is bad

    $userEntity = new UserEntity();

    $userEntity->setIdentifier($username);

    return $userEntity;
  }
}
