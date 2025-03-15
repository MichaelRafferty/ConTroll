<?php

declare(strict_types=1);

use \Defuse\Crypto\Key;
use Laminas\Diactoros\Stream;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RegOAuth2\Repositories\AccessTokenRepository;
use RegOAuth2\Repositories\ClientRepository;
use RegOAuth2\Repositories\ScopeRepository;
use Slim\Factory\AppFactory;

require_once('lib/base.php');
require_once('oauthRepositories/ClientRepository.php');
require_once('oauthRepositories/ScopeRepository.php');
require_once('oauthRepositories/AccessTokenRepository.php');
$api = get_conf("api");

// build the repositoriers
$clientRepository = new ClientRepository(); // instance of ClientRepositoryInterface
$scopeRepository = new ScopeRepository(); // instance of ScopeRepositoryInterface
$accessTokenRepository = new AccessTokenRepository(); // instance of AccessTokenRepositoryInterface

// Path to public and private keys
$privateKey = 'file://' . __DIR__ . '/../config/oauth.ppk';

// Setup the authorization server
$server = new AuthorizationServer(
    $clientRepository,
    $accessTokenRepository,
    $scopeRepository,
    $privateKey,
    Key::loadFromAsciiSafeString(get_oauthkey())
);

// Enable the client credentials grant on the server
$server->enableGrantType(
    new ClientCredentialsGrant(),
    new DateInterval('PT1H') // access tokens will expire after 1 hour
);

$app = AppFactory::create(); // create the app holding point
$app->addErrorMiddleware(get_debug('api') != 0, true, true); // disable long error messages

$app->post('/client_credentials.php/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($server) {
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

$app->run();
