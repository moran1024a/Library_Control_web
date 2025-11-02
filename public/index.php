<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\BookController;
use App\Controllers\BorrowController;
use App\Controllers\UserController;
use App\Core\Router;

$router = new Router();

$bookController = new BookController();
$router->add('GET', '/books', [$bookController, 'index']);
$router->add('POST', '/books/add', [$bookController, 'store']);
$router->add('POST', '/books/edit/{id}', [$bookController, 'update']);
$router->add('POST', '/books/delete/{id}', [$bookController, 'delete']);
$router->add('GET', '/search/books', [$bookController, 'search']);

$userController = new UserController();
$router->add('POST', '/register', [$userController, 'register']);
$router->add('POST', '/login', [$userController, 'login']);
$router->add('GET', '/logout', [$userController, 'logout']);
$router->add('POST', '/user/update', [$userController, 'update']);

$borrowController = new BorrowController();
$router->add('POST', '/borrow/{book_id}', [$borrowController, 'borrow']);
$router->add('POST', '/return/{record_id}', [$borrowController, 'return']);
$router->add('GET', '/records', [$borrowController, 'records']);
$router->add('GET', '/search/records', [$borrowController, 'search']);
$router->add('GET', '/stats/borrow', [$borrowController, 'stats']);
$router->add('GET', '/check/overdue', [$borrowController, 'checkOverdue']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

if ($method === 'GET' && ($uri === '/' || $uri === '')) {
    require __DIR__ . '/../app/views/home.php';
    exit;
}

$router->dispatch($method, $uri);
