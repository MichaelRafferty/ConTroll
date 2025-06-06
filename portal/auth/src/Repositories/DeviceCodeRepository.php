<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\ClientEntity;
use ControllAuth\Entities\DeviceCodeEntity;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

class DeviceCodeRepository implements DeviceCodeRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function getNewDeviceCode(): DeviceCodeEntityInterface
  {
    return new DeviceCodeEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void
  {
    // Some logic to persist a new device code to a database
  }

  /**
   * {@inheritdoc}
   */
  public function getDeviceCodeEntityByDeviceCode($deviceCode): ?DeviceCodeEntityInterface
  {
    $clientEntity = new ClientEntity();
    $clientEntity->setIdentifier('controll');

    $deviceCodeEntity = new DeviceCodeEntity();

    $deviceCodeEntity->setIdentifier($deviceCode);
    $deviceCodeEntity->setExpiryDateTime(new DateTimeImmutable('now +1 hour'));
    $deviceCodeEntity->setClient($clientEntity);

    // The user identifier should be set when the user authenticates on the
    // OAuth server, along with whether they approved the request
    $deviceCodeEntity->setUserApproved(true);
    $deviceCodeEntity->setUserIdentifier('1');

    return $deviceCodeEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function revokeDeviceCode($codeId): void
  {
    // Some logic to revoke device code
  }

  /**
   * {@inheritdoc}
   */
  public function isDeviceCodeRevoked($codeId): bool
  {
    return false;
  }
}
