<?php

declare(strict_types=1);

namespace RegOAuth2\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAuth2ServerExamples\Entities\ScopeEntity;

use function array_key_exists;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($scopeIdentifier): ?ScopeEntityInterface
    {
        $scopes = [
            'basic' => [
                'description' => 'Basic details about you',
            ],
            'email' => [
                'description' => 'Your email address',
            ],
        ];

        if (array_key_exists($scopeIdentifier, $scopes) === false) {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);

        return $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null,
        $authCodeId = null
    ): array {
        // Example of programatically modifying the final scope of the access token
        if ((int) $userIdentifier === 1) {
            $scope = new ScopeEntity();
            $scope->setIdentifier('email');
            $scopes[] = $scope;
        }

        return $scopes;
    }
}
