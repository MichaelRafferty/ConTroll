<?php

declare(strict_types=1);

namespace ControllAuth\Entities;

use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class UserEntity implements UserEntityInterface
{
  use EntityTrait;

  /**
   * Return the user's identifier.
   */
  public function getIdentifier(): string
  {
    // TODO: TEMPORARY! It's expected this will be set by the instantiator with the user's ID and we can eliminate this
    // and let the trait handle getIdentifier()

    return '1';
  }
}
