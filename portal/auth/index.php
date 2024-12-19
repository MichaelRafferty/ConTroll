<?php

declare(strict_types=1);

// TODO: This can be removed after we're happy with operations
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use ControllAuth\Entities\UserEntity;
use ControllAuth\Repositories\AccessTokenRepository;
use ControllAuth\Repositories\AuthCodeRepository;
use ControllAuth\Repositories\ClientRepository;
use ControllAuth\Repositories\RefreshTokenRepository;
use ControllAuth\Repositories\ScopeRepository;
use ControllAuth\Repositories\UserRepository;
use Laminas\Diactoros\Stream;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Middleware\AuthorizationServerMiddleware;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

require_once("lib/base.php");
include __DIR__ . '/../../Composer/vendor/autoload.php';

$app = new App([

  $debug_conf = get_conf('debug');
  $oauth_conf = get_conf('oauth');
  // TODO: This may leak information. Or not. Let's find out after we're up and running
  'settings' => [
    'displayErrorDetails' => $debug_conf['oauth'];
  ],

  // Add the authorization server to the DI container

  AuthorizationServer::class => static function () {

    // Set up the authorization server

    $clientRepository = new ClientRepository();
    $scopeRepository = new ScopeRepository();
    $accessTokenRepository = new AccessTokenRepository();
    $authCodeRepository = new AuthCodeRepository();
    $refreshTokenRepository = new RefreshTokenRepository();

    $server = new AuthorizationServer(
      $clientRepository,
      $accessTokenRepository,
      $scopeRepository,
      'file://' . __DIR__ . '/../../config/oauthPrivate.key',              // path to private key
      $oauth_conf['serverKey'] // encryption key
    );

    // Enable the authentication code grant on the server with a token TTL of 24 hours (as per Syd)
    // The code has a TTL of 10 minutes. We can increase this if we want, but really, it should be used immediately

    $server->enableGrantType(
      new AuthCodeGrant(
        $authCodeRepository,
        $refreshTokenRepository,
        new DateInterval('PT10M')
      ),
      new DateInterval('PT24H')
    );

    $passwordGrant = new PasswordGrant(
      new UserRepository(),                  // instance of UserRepositoryInterface
      new RefreshTokenRepository()    // instance of RefreshTokenRepositoryInterface
    );

    $passwordGrant->setRefreshTokenTTL(new DateInterval('P1M')); // refresh tokens will expire after 1 month

    $server->enableGrantType(
      $passwordGrant,
      new DateInterval('PT24H') // access tokens will expire after 24 hours
    );

    $server->enableGrantType(
      new ClientCredentialsGrant(),
      new DateInterval('PT24H') // access tokens will expire after 24 hours
    );

    // Enable the refresh token grant on the server with a token TTL of 1 month

    $server->enableGrantType(
      new RefreshTokenGrant($refreshTokenRepository),
      new DateInterval('P1M')
    );

    return $server;
  },
  ResourceServer::class => static function () {
    $publicKeyPath = 'file://' . __DIR__ . '/../../config/oauthPublic.key';

    $server = new ResourceServer(
      new AccessTokenRepository(),
      $publicKeyPath
    );

    return $server;
  },
]);

$app->get('/authorize', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  /* @var AuthorizationServer $server */
  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {
    // Validate the HTTP request and return an AuthorizationRequest object.
    // The auth request object can be serialized into a user's session
    $authRequest = $server->validateAuthorizationRequest($request);

    // Once the user has logged in set the user on the AuthorizationRequest
    $authRequest->setUser(new UserEntity());

    // Once the user has approved or denied the client update the status
    // (true = approved, false = denied)
    $authRequest->setAuthorizationApproved(true);

    // Return the HTTP redirect response
    return $server->completeAuthorizationRequest($authRequest, $response);
  } catch (OAuthServerException $exception) {
    return $exception->generateHttpResponse($response);
  } catch (Exception $exception) {
    $body = new Stream('php://temp', 'r+');
    $body->write($exception->getMessage());

    return $response->withStatus(500)->withBody($body);
  }
});

$app->post('/password/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  /* @var AuthorizationServer $server */
  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {
    // Try to respond to the access token request
    return $server->respondToAccessTokenRequest($request, $response);
  } catch (OAuthServerException $exception) {
    // All instances of OAuthServerException can be converted to a PSR-7 response
    return $exception->generateHttpResponse($response);
  } catch (Exception $exception) {
    // Catch unexpected exceptions
    $body = $response->getBody();
    $body->write($exception->getMessage());

    return $response->withStatus(500)->withBody($body);
  }
});

$app->post('/client/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  /* @var AuthorizationServer $server */
  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {
    // Try to respond to the request
    return $server->respondToAccessTokenRequest($request, $response);
  } catch (OAuthServerException $exception) {
    // All instances of OAuthServerException can be formatted into a HTTP response
    return $exception->generateHttpResponse($response);
  } catch (Exception $exception) {
    // Unknown exception
    $body = new Stream('php://temp', 'r+');
    $body->write($exception->getMessage());

    return $response->withStatus(500)->withBody($body);
  }
});

$app->post('/refresh', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  /* @var AuthorizationServer $server */
  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {
    return $server->respondToAccessTokenRequest($request, $response);
  } catch (OAuthServerException $exception) {
    return $exception->generateHttpResponse($response);
  } catch (Exception $exception) {
    $body = new Stream('php://temp', 'r+');
    $body->write($exception->getMessage());

    return $response->withStatus(500)->withBody($body);
  }
});

// Access token issuer
$app->post('/token', function (): void {
})->add(new AuthorizationServerMiddleware($app->getContainer()->get(AuthorizationServer::class)));

// Secured API
$app->group('/api', function (): void {
  $this->get('/user', function (ServerRequestInterface $request, ResponseInterface $response) {
    $params = [];

    if (in_array('basic', $request->getAttribute('oauth_scopes', []))) {
      $params = [
        'perid'   => 1,
        'username' => 'Thoth',
        'rights' => ['hugo_nominate', 'hugo_vote'],
      ];
    }

    if (in_array('email', $request->getAttribute('oauth_scopes', []))) {
      $params['email'] = 'thoth@example.com';
      $params['validated_email'] = 'thoth@example.com';
    }

    if (in_array('vote', $request->getAttribute('oauth_scopes', []))) {
      $params['legal_name'] = 'Thoth McThotherson';
      $params['full_name'] = 'Thoth McThotherson';
      $params['first_name'] = 'Thoth';
      $params['last_name'] = 'McThotherson';
      $params['rights'] = ['hugo_vote'];
    }

    $body = new Stream('php://temp', 'r+');
    $body->write(json_encode($params));

    return $response->withBody($body);
  });
})->add(new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)));

$app->run();
