<?php

// TODO: Remove once dependencies with deprecation errors are updated
error_reporting(E_ALL ^ E_DEPRECATED);

use Dotenv\Dotenv;
use Jalle19\HsDebaiter\Application;
use Jalle19\HsDebaiter\Http\ArticleController;
use Jalle19\HsDebaiter\Http\CategoryController;
use Jalle19\HsDebaiter\Http\ErrorHandler;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require_once(__DIR__ . '/../vendor/autoload.php');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Initialize the container and router
$app = new Application();
$container = $app->getContainer();
$router = $app->getRouter($container);

// Configure routes
$router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
    $response = new Response();
    $response->getBody()->write('hs-debaiter');

    return $response;
});

$router->map('GET', '/articles/todays-changed', [ArticleController::class, 'getTodaysChangedArticles']);
$router->map('GET', '/articles/frequently-changed', [ArticleController::class, 'getFrequentlyChangedArticles']);
$router->map('GET', '/articles/category/{category}', [ArticleController::class, 'getCategoryArticles']);
$router->map('GET', '/articles/search', [ArticleController::class, 'searchArticles']);
$router->map('GET', '/article/{guid}', [ArticleController::class, 'getArticle']);
$router->map('GET', '/categories', [CategoryController::class, 'getCategories']);

// Pass the request through the router and emit response
$factory = new Psr17Factory();
$creator = new ServerRequestCreator($factory, $factory, $factory, $factory);
$request = $creator->fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (\Throwable $e) {
    /** @var ErrorHandler $errorHandler */
    $errorHandler = $container->get(ErrorHandler::class);
    $response = $errorHandler->createErrorResponse($e);
}


(new SapiEmitter())->emit($response);
