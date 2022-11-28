<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


use App\Middleware\AuthMiddleware;
use League\Route\RouteGroup;
use Tracy\Debugger;

Debugger::enable();

$router = require ('app/config/config.php');

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

$router->map('GET', '/', 'App\FrontEndController::index');
$router->map('GET', '/article/{id:number}', 'App\FrontEndController::article');

$router->map('GET', '/signin', 'App\BackEndController::showSignInForm');
$router->map('POST', '/signin', 'App\BackEndController::UserSignIn');
$router->map('GET', '/signup', 'App\BackEndController::showSignUpForm');
$router->map('POST', '/signup', 'App\BackEndController::UserSignUp');

$router->group('/admin', function (RouteGroup $router) {
    $router->map('GET', '/', 'App\BackEndController::index');
    $router->map('GET', '/logout', 'App\BackEndController::userLogOut');
    $router->map('GET', '/users', 'App\BackEndController::showUsersList');

    $router->map('GET', '/articles', 'App\BackEndController::showArticlesList');
    $router->map('GET', '/article-add', 'App\BackEndController::showAddArticleForm');
    $router->map('POST','/article-add', 'App\BackEndController::AddArticle');
    $router->map('GET','/article-edit/{id:number}','App\BackEndController::showEditArticleForm');
    $router->map('GET','/article-delete/{id:number}', 'App\BackEndController::deleteArticle');

    $router->map('GET', '/tags', 'App\BackEndController::showTagsList');
    $router->map('GET', '/tag-add', 'App\BackEndController::showAddTagForm');
    $router->map('POST','/tag-add', 'App\BackEndController::AddTag');
    $router->map('GET','/tag-delete/{id:number}', 'App\BackEndController::deleteTag');

    $router->map('GET', '/categories', 'App\BackEndController::showCategoriesList');
    $router->map('GET', '/category-add', 'App\BackEndController::showAddCategoryForm');
    $router->map('POST','/category-add', 'App\BackEndController::AddCategory');
    $router->map('GET','/category-delete/{id:number}', 'App\BackEndController::deleteCategory');
})->middleware(new AuthMiddleware);

$response = $router->dispatch($request);

// send the response to the browser
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);