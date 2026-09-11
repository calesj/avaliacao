<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// O servidor embutido (php -S) não tem regra de rewrite: sem isto ele
// mandaria os arquivos de assets para o front controller.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . $path)) {
    return false;
}

/** @var Router $router */
$router = require __DIR__ . '/../bootstrap/app.php';

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
