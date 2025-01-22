<?php

declare(strict_types=1);

namespace ControllAuth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    private string $identifier;
    private string $name;
    private array $redirectUris = [];
    private bool $isConfidential = true;

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setRedirectUris(array $redirectUris): void
    {
        $this->redirectUris = $redirectUris;
    }

    public function getRedirectUri(): array
    {
        return $this->redirectUris;
    }

    public function setConfidential(bool $isConfidential = true): void
    {
        $this->isConfidential = $isConfidential;
    }

    public function isConfidential(): bool
    {
        return $this->isConfidential;
    }
}
