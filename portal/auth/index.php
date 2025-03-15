<?php

declare(strict_types=1);

/**
 * This file sets up a Slim-based OAuth2 server using the 
 * League OAuth2 Server library. The server handles multiple 
 * grant types (Auth Code, Password, Client Credentials, Refresh),
 * and also provides protected API endpoints that require 
 * valid access tokens.
 */

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
use Laminas\Diactoros\Response\JsonResponse;
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

session_start();

require_once("lib/base.php");
include __DIR__ . '/../../Composer/vendor/autoload.php';

// Retrieve debug configuration and OAuth-related configuration
$debug_conf = get_conf('debug');
$oauth_conf = get_conf('oauth');

/**
 * Create a new Slim application instance. 
 * Here, we configure:
 *  - 'displayErrorDetails': whether to show error details (usually disabled in prod)
 *  - An instance of AuthorizationServer (bound to the container)
 *  - An instance of ResourceServer (bound to the container)
 */
$app = new App([

  'settings' => [
    'displayErrorDetails' => $debug_conf['oauth']
  ],

  // Add the AuthorizationServer to the DI container.
  AuthorizationServer::class => static function () {
    // Set up the repositories required by the OAuth2 Server.
    $clientRepository = new ClientRepository();
    $scopeRepository = new ScopeRepository();
    $accessTokenRepository = new AccessTokenRepository();
    $authCodeRepository = new AuthCodeRepository();
    $refreshTokenRepository = new RefreshTokenRepository();

    // Create the authorization server instance
    $server = new AuthorizationServer(
      $clientRepository,
      $accessTokenRepository,
      $scopeRepository,
      'file://' . __DIR__ . '/../../config/oauthPrivate.key', // Path to the private key
      'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
      // Alternatively use: $oauth_conf['serverKey']
    );

    // Enable the Auth Code Grant
    //  - Auth codes expire in 10 minutes
    //  - The resulting access token expires in 24 hours
    $server->enableGrantType(
      new AuthCodeGrant(
        $authCodeRepository,
        $refreshTokenRepository,
        new DateInterval('PT10M') // Auth code TTL
      ),
      new DateInterval('PT24H') // Access token TTL
    );

    // Enable the Password Grant
    //  - Refresh tokens expire in 1 month
    //  - Access tokens expire in 24 hours
    $passwordGrant = new PasswordGrant(
      new UserRepository(),
      new RefreshTokenRepository()
    );
    $passwordGrant->setRefreshTokenTTL(new DateInterval('P1M')); // Refresh token TTL
    $server->enableGrantType(
      $passwordGrant,
      new DateInterval('PT24H') // Access token TTL
    );

    // Enable the Client Credentials Grant
    //  - Access tokens expire in 24 hours
    $server->enableGrantType(
      new ClientCredentialsGrant(),
      new DateInterval('PT24H')
    );

    // Enable the Refresh Token Grant
    //  - Access tokens expire in 1 month
    $server->enableGrantType(
      new RefreshTokenGrant($refreshTokenRepository),
      new DateInterval('P1M')
    );

    return $server;
  },

  // Add the ResourceServer to the DI container
  ResourceServer::class => static function () {
    $publicKeyPath = 'file://' . __DIR__ . '/../../config/oauthPublic.key';

    // Create the resource server instance
    return new ResourceServer(
      new AccessTokenRepository(),
      $publicKeyPath
    );
  },
]);

$container = $app->getContainer();

/**
 * Overwrite the default request object in the container to include 
 * a custom base path (/auth) if you deploy under a subdirectory.
 */
$container['request'] = function ($container) {
    $environment = $container->get('environment');
    $request = \Slim\Http\Request::createFromEnvironment($environment);

    // Set your subdirectory here
    $request = $request->withUri($request->getUri()->withBasePath('/auth'));

    return $request;
};

/**
 * Custom notFoundHandler to override Slim's default 404 handling.
 * Can be used for logging or returning a custom message.
 */
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        // Optional logging of request details for debugging could go here

        // Return a custom 404 response
        return $response->withStatus(404)
            ->write("ConTroll Auth System: 404<br /><br />\n" .
                    "Path: " . $request->getUri()->getPath() . '<br /><br />');
    };
};

/**
 * GET /
 * Basic index route. Shows a message to confirm that the OAuth server 
 * is running, but no actual content is served here.
 */
$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
  $body = new Stream('php://temp', 'r+');
  $body->write('ConTroll Auth System. Nothing to see here. Have a lovely day.');

  return $response->withStatus(200)->withBody($body);
});

/**
 * GET /authorize
 *
 * Validates the client’s authorization request,
 * then redirects the user to an external login flow.
 */
$app->get('/authorize', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
    /** @var AuthorizationServer $server */
    $server = $app->getContainer()->get(AuthorizationServer::class);

    try {
      // Validate the incoming OAuth 2.0 request
      $authRequest = $server->validateAuthorizationRequest($request);

      // Store the auth request in the session so we can retrieve it later
      $_SESSION['authRequest'] = serialize($authRequest);

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

    } catch (OAuthServerException $e) {
        // Return standardized OAuth error response
        return $e->generateHttpResponse($response);
    } catch (\Exception $e) {
        // Catch any other errors
        $body = new Stream('php://temp', 'r+');
        $body->write($e->getMessage());
        return $response->withStatus(500)->withBody($body);
    }
});

/**
 * GET /authcomplete
 *
 * Called by the external system once the user has logged in.
 * Retrieves the stored AuthRequest from the session and completes it.
 */
$app->get('/authcomplete', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
    /** @var AuthorizationServer $server */
    $server = $app->getContainer()->get(AuthorizationServer::class);

    try {
        // Retrieve the stored AuthRequest from the session

        /** @var AuthorizationRequest $authRequest */
        $authRequest = unserialize($_SESSION['authRequest']);

        // Clear it from session to avoid reuse 
        unset($_SESSION['authRequest']);

        if (!array_key_exists('oauth', $_REQUEST)) {
          $body = new Stream('php://temp', 'r+');
          $body->write("No oauth parameter received");
          return $response->withStatus(400)->withBody($body);
        }

        // [email] => chris.ambler@seattlein2025.org
        // [perid] => 1020607
        // [newperid] => 11
        // [resType] => Nom
        // [legalName] =>
        // [first_name] => Christopher
        // [last_name] => Ambler
        // [fullName] => Christopher Ambler
        // [rights] =>
    
        $args = decryptCipher($_GET['oauth'], true);
    
        if ($args == null || $args == '') {
          $body = new Stream('php://temp', 'r+');
          $body->write("Invalid oauth parameter received");
          return $response->withStatus(400)->withBody($body);
        }

	/*
   
        $iQ = <<<EOS
insert into oauthAccessTokens (id, userId, clientId, scopes, expiresAt, revoked, email, perid, newperid, legalName, firstName, lastName, fullName, rights, resType)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

$timestamp = time(); // Current Unix timestamp
$expiresAt = date('Y-m-d H:i:s', $timestamp); // Convert to 'Y-m-d H:i:s' format

        $iKey = dbSafeInsert($iQ, 'sisssisiissssss', array(
	  'testidhere',
	  1,
	  'nomnom',
	  'basic',
	  $expiresAt,
	  0,
	  $args['email'],
	  $args['perid'],
	  $args['newperid'],
	  $args['legalName'],
	  $args['first_name'],
	  $args['last_name'],
	  $args['fullName'],
	  $args['rights'],
	  $args['resType']
	));

        if ($iKey === false) {
	  $body = new Stream('php://temp', 'r+');
          $body->write("SQL Fail");
          return $response->withStatus(400)->withBody($body);
        }

	*/

	$request = $request->withAttribute('oauth_user_id', $args['email']);

        // Create/fetch a user entity for League's AuthorizationRequest
        $userEntity = new \ControllAuth\Entities\UserEntity();
        $userEntity->setIdentifier($args['email']);

        // Attach the user to the auth request and mark it as approved
        $authRequest->setUser($userEntity);
        $authRequest->setAuthorizationApproved(true);

        // Complete the authorization, which will redirect back to the original client
        $retVal = $server->completeAuthorizationRequest($authRequest, $response);

        //$body = new Stream('php://temp', 'r+');
        //$body->write("Auth Return<br /><br />");
        //$body->write("<pre>" . print_r($authRequest, true) . "</pre>");
        //$body->write("<pre>" . print_r($response, true) . "</pre>");
        //$body->write("<pre>" . print_r($args, true) . "</pre>");

        //$retVal = $response->withStatus(200)->withBody($body);

	return $retVal;

    } catch (OAuthServerException $e) {
        return $e->generateHttpResponse($response);
    } catch (\Exception $e) {
        $body = new Stream('php://temp', 'r+');
        $body->write($e->getMessage());
        return $response->withStatus(500)->withBody($body);
    }
});

/**
 * POST /password/access_token
 * This route handles Password Grant requests. 
 * Expects 'username' and 'password' (and usually 'client_id' 
 * and 'client_secret') as part of the POST body.
 */
$app->post('/password/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
  /** @var AuthorizationServer $server */
  $server = $app->getContainer()->get(AuthorizationServer::class);

  try {
    // Respond to an access token request (Password Grant)
    return $server->respondToAccessTokenRequest($request, $response);
  } catch (OAuthServerException $exception) {
    return $exception->generateHttpResponse($response);
  } catch (Exception $exception) {
    $body = $response->getBody();
    $body->write($exception->getMessage());
    return $response->withStatus(500)->withBody($body);
  }
});

/**
 * POST /client/access_token
 * This route handles Client Credentials Grant requests.
 * Expects 'client_id' and 'client_secret' in the POST body.
 */
$app->post('/client/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
  /** @var AuthorizationServer $server */
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

/**
 * POST /refresh
 * This route handles Refresh Token Grant requests.
 * Expects 'refresh_token' (plus 'client_id' and 'client_secret')
 */
$app->post('/refresh', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
  /** @var AuthorizationServer $server */
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

/**
 * POST /token
 * Example route for acquiring a token. 
 * Protected with AuthorizationServerMiddleware, meaning it 
 * processes the incoming request for token generation
 *
 */
$app->post('/token', function (): void {
    // No direct logic here.
})->add(new AuthorizationServerMiddleware($app->getContainer()->get(AuthorizationServer::class)));

/**
 * Secured API group 
 * /api routes are protected by the ResourceServerMiddleware, 
 * so any request must carry a valid access token.
 */
$app->group('/api', function (): void {

  /**
   * GET /api/user
   * Example user endpoint that returns user data 
   * based on scopes included in the access token.
   */
  $this->get('/user', function (ServerRequestInterface $request, ResponseInterface $response) {
    // Prepare an array of user parameters, conditionally 
    // filled depending on the scopes present.

    $attributes = $request->getAttributes();

    $scopes = $request->getAttribute('oauth_scopes', []);
    $userEmail = $request->getAttribute('oauth_user_id'); // Assuming `oauth_user_id` is in the claims

    if (!$userEmail) {
      return new JsonResponse(['error' => 'Unauthorized: User ID not found'], 401);
    }

    $personSQL = " SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns, p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.managedBy, p.lastVerified, 'p' AS personType, TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname FROM perinfo p WHERE p.email_addr = ?;";

    $personR = dbSafeQuery($personSQL, 's', array($userMail));

    if ($personR === false) {
      $body = new Stream('php://temp', 'r+');
      $body->write("SQL Fail:\n\n $personSQL");

      return $response->withBody($body);
    }

    if ($personR->num_rows == 0) {
      $body = new Stream('php://temp', 'r+');
      $body->write("No Results:\n\n $personSQL");

      return $response->withBody($body);
    }

    $info = $personR->fetch_assoc();

    $personR->free();

    $params = [];

    // If the token contains the 'basic' scope
    if (in_array('basic', $request->getAttribute('oauth_scopes', []))) {
      $params = [
        'perid'    => $info['id'],
        'first_name' => $info['first_name'],
        'last_name' => $info['last_name'],
        'full_name' => $info['fullname'],
      ];
    }

    // If the token contains the 'email' scope
    if (in_array('email', $request->getAttribute('oauth_scopes', []))) {
      $params['email'] = $userEmail;
      $params['validated_email'] = $info['email_addr'];
    }

    // If the token contains the 'vote' scope
    if (in_array('vote', $request->getAttribute('oauth_scopes', []))) {
      $params['full_name']  = $info['fullname'];
      $params['first_name'] = $info['first_name'];
      $params['last_name']  = $info['last_name'];
      $params['rights']     = ['hugo_vote'];
    }

    // Return the assembled JSON response
    $body = new Stream('php://temp', 'r+');
    $body->write(json_encode($params));

    return $response->withBody($body);
  });

})->add(new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)));

/**
 * Finally, run the Slim application to start handling requests.
 */
$app->run();

