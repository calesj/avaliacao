<?php

declare(strict_types=1);

namespace Core;

readonly class Request
{
    private function __construct(
        private string $method,
        private string $path,
        private array $query,
    ) {
    }

    public static function capture(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            $_GET,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $chave, string $padrao = ''): string
    {
        $valor = $this->query[$chave] ?? $padrao;

        // ?busca[]=x chega como array; sem esta checagem o cast emitiria warning.
        return is_string($valor) ? trim($valor) : $padrao;
    }
}
