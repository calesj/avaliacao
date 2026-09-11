<?php

declare(strict_types=1);

use App\Controllers\CustomerController;
use Core\Response;
use Core\Router;

return function (Router $router): void {
    $router->get('/', fn () => Response::make(file_get_contents(BASE_PATH . '/src/Views/relatorio.html')));

    $router->get('/api/customers', [CustomerController::class, 'index']);
};
