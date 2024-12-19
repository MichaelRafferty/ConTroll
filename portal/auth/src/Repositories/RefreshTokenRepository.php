<?php

declare(strict_types=1);

namespace ControllAuth\Repositories;

use ControllAuth\Entities\RefreshTokenEntity;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
  /**
   * {@inheritdoc}
   */
  public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
  {
    // Some logic to persist the refresh token in a database
  }

  /**
   * {@inheritdoc}
   */
  public function revokeRefreshToken($tokenId): void
  {
    // Some logic to revoke the refresh token in a database
  }

  /**
   * {@inheritdoc}
   */
  public function isRefreshTokenRevoked($tokenId): bool
  {
    return false; // The refresh token has not been revoked
  }

  /**
   * {@inheritdoc}
   */
  public function getNewRefreshToken(): ?RefreshTokenEntityInterface
  {
    return new RefreshTokenEntity();
  }
}
