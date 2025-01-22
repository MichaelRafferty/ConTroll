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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
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

$debug_conf = get_conf('debug');
$oauth_conf = get_conf('oauth');

$app = new App([

  // TODO: This may leak information. Or not. Let's find out after we're up and running
  'settings' => [
    'displayErrorDetails' => $debug_conf['oauth']
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
      'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
      // $oauth_conf['serverKey'] 
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

$container = $app->getContainer();

// Set the basePath dynamically
$container['request'] = function ($container) {
    $environment = $container->get('environment');
    $request = \Slim\Http\Request::createFromEnvironment($environment);

    // Set your subdirectory here
    $request = $request->withUri($request->getUri()->withBasePath('/auth'));

    return $request;
};

// Define a custom notFoundHandler
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        // Log request details for debugging
        //echo ("404 Error: Path = " . $request->getUri()->getPath()) . '<br /><br />';
        //echo ("Method = " . $request->getMethod()) . '<br /><br />';
        //echo ("Query Params = " . json_encode($request->getQueryParams())) . '<br /><br />';
        //echo ("Headers = " . json_encode($request->getHeaders())) . '<br /><br />';
	//echo "<br /><br /><pre>" . print_r($request, true) . "</pre>";

        // Optionally return a custom response
        return $response->withStatus(404)
                        ->write("ConTroll Auth System: 404<br /><br />\n" .
                                "Path: " . $request->getUri()->getPath() . '<br /><br />');
    };
};


$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
  $body = new Stream('php://temp', 'r+');
  // $out = print_r($request, true);
  // $body->write('<pre>' . $out . '</pre/');
  $body->write('ConTroll Auth System. Nothing to see here. Have a lovely day.');
    
  return $response->withStatus(200)->withBody($body);
});

$app->get('/authcomplete', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {

    $portal_conf = get_conf('portal');
    $redirect = $portal_conf['portalsite'];

    if (!array_key_exists('oauth', $_REQUEST)) {
      echo "<h1>No oauth parameter passed</h1>";
      exit();
    }

    $args = decryptCipher($_GET['oauth'], true);

    if ($args == null || $args == '') {
      echo '<h1>Invalid oauth parameter passed</h1>';
      exit();
    }

    echo "AuthReturn: <br /><br />";
    echo "<pre>" . print_r($args, true) . "</pre>";

    $authRequest->setUser(new UserEntity());

    $authRequest->setAuthorizationApproved(true);

    // Return the HTTP redirect response

    return $server->completeAuthorizationRequest($authRequest, $response);

  } catch (OAuthServerException $exception) {

    return $exception->generateHttpResponse($response);

  } catch (Exception $exception) {

    $body = new Stream('php://temp', 'r+');
    $body->write($exception->getMessage());
    $body->write("Config: " . print_r($oauth_conf, true));

    return $response->withStatus(500)->withBody($body);
  }
});

$app->get('/authorize', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {

    $authRequest = $server->validateAuthorizationRequest($request);

    $portal_conf = get_conf('portal');
    $redirect = $portal_conf['portalsite'];

    $retdata = 'Nom';
    $returl = $portal_conf['portalsite'] . '/auth/authcomplete';
    $apikey = 'testAPIkey';
    $app = 'Test Harness';

    $args = json_encode(array('retdata' => $retdata, 'returl' => $returl, 'apikey' => $apikey, 'app' => $app ));

    $oauth = encryptCipher($args, true);
    $url = "$redirect?oauth=$oauth";

    return $response->withHeader('Location', $url)->withStatus(302);

  } catch (OAuthServerException $exception) {

    return $exception->generateHttpResponse($response);

  } catch (Exception $exception) {

    $body = new Stream('php://temp', 'r+');
    $body->write($exception->getMessage());
    $body->write("Config: " . print_r($oauth_conf, true));

    return $response->withStatus(500)->withBody($body);
  }
});

$app->post('/password/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {

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

// X: Acquire a token

$app->post('/token', function (): void {
})->add(new AuthorizationServerMiddleware($app->getContainer()->get(AuthorizationServer::class)));

// LAST: Secured API

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

