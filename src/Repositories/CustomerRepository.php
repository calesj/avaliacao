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

        $customers = array_values(array_filter(array_map($this->sanitize(...), $rows)));
        $discarded = count($rows) - count($customers);

        if ($discarded > 0) {
            error_log("CustomerRepository: {$discarded} registro(s) descartado(s) por inconsistência.");
        }

        return $customers;
    }

    /**
     * Devolve o registro limpo, ou null se ele não puder ser confiado.
     * Um cadastro inconsistente é descartado sem derrubar o relatório inteiro.
     */
    private function sanitize(mixed $row): ?array
    {
        if (! is_array($row)) {
            return null;
        }

        foreach (['id', 'nome', 'email', 'cidade', 'telefone'] as $field) {
            if (trim((string) ($row[$field] ?? '')) === '') {
                return null;
            }
        }

        $email = strtolower(trim((string) $row['email']));

        // A fonte é editada à mão, então o telefone pode vir com máscara.
        $telefone = preg_replace('/\D/', '', (string) $row['telefone']);

        if (! is_numeric($row['id']) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        if (! in_array(strlen($telefone), [10, 11], true)) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'nome' => trim((string) $row['nome']),
            'email' => $email,
            'cidade' => trim((string) $row['cidade']),
            'telefone' => $telefone,
        ];
    }
}
