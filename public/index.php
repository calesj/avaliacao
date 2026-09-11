<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;

/** @var Router $router */
$router = require __DIR__ . '/../bootstrap/app.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

try {
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $exception) {
    error_log((string) $exception);

    $message = 'Erro interno ao processar a requisição.';

    $response = $router->expectsJson($path)
        ? Response::json(['erro' => $message], 500)
        : Response::make($message, 500, 'text/plain; charset=utf-8');
}

$response->send();
