<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;
use Throwable;

readonly class CustomerRepository
{
    public function __construct(private string $path)
    {
    }

    public function all(): array
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new RuntimeException("Fonte de dados inacessível: {$this->path}");
        }

        try {
            $rows = require $this->path;
        } catch (Throwable $exception) {
            // Erro de sintaxe no arquivo de dados chega aqui como ParseError.
            throw new RuntimeException('Falha ao interpretar a fonte de dados.', previous: $exception);
        }

        if (! is_array($rows)) {
            throw new RuntimeException('Fonte de dados não retornou uma lista de registros.');
        }

        return $rows;
    }
}
