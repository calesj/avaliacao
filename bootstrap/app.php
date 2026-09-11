<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\CustomerRepository;
use Core\Container;
use Core\Router;

error_reporting(E_ALL);
ini_set('display_errors', '0'); // erro do PHP nunca deve vazar dentro do corpo da resposta
ini_set('log_errors', '1');
date_default_timezone_set('America/Sao_Paulo');

define('BASE_PATH', dirname(__DIR__));

$container = new Container();

// Único ponto do projeto que sabe onde a fonte de dados mora. Aqui a gente trocaria pela conexão com o banco de dados
$container->singleton(
    CustomerRepository::class,
    fn () => new CustomerRepository(BASE_PATH . '/data/clientes.php'),
);

$router = new Router($container);

(require BASE_PATH . '/routes/web.php')($router);

return $router;
