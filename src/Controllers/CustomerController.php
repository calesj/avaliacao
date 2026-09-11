<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CustomerRepository;
use Core\Response;

readonly class CustomerController
{
    public function __construct(private CustomerRepository $repository)
    {}

    public function index(): Response
    {
        $customers = $this->repository->all();

        return Response::json($customers);
    }
}
