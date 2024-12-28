<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once('lib/base.php');

$app = AppFactory::create();

$app->get('/hello.php', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!\n");
    return $response;
});

$app->get('/hello.php/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello $name!\n");
    return $response;
});

$app->run();
