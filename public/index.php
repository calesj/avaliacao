<?php

declare(strict_types=1);

use Core\Request;
use Core\Response;
use Core\Router;

/** @var Router $router */
$router = require __DIR__ . '/../bootstrap/app.php';

$request = Request::capture();

// O servidor embutido (php -S) não tem regra de rewrite: sem isto ele
// mandaria os arquivos de assets para o front controller.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . $request->path())) {
    return false;
}

try {
    $response = $router->dispatch($request);
} catch (Throwable $exception) {
    error_log((string) $exception);

    $message = 'Erro interno ao processar a requisição.';

    $response = $router->expectsJson($request->path())
        ? Response::json(['erro' => $message], 500)
        : Response::make($message, 500, 'text/plain; charset=utf-8');
}

$response->send();
