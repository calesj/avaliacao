<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CustomerRepository;
use Core\Request;
use Core\Response;

readonly class CustomerController
{
    public function __construct(private CustomerRepository $repository)
    {}

    public function index(Request $request): Response
    {
        $customers = $this->repository->all();

        $search = mb_strtolower($request->query('busca'));

        if ($search !== '') {
            $customers = array_filter($customers, fn (array $customer) => str_contains(
                mb_strtolower("{$customer['nome']} {$customer['email']} {$customer['cidade']}"),
                $search,
            ));
        }

        // array_filter preserva as chaves originais; sem array_values o JSON
        // sairia como objeto {"3": {...}} em vez de lista.
        return Response::json(array_values($customers));
    }
}
