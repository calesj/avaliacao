<?php

declare(strict_types=1);

namespace Core;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];

    /**
     * @throws ReflectionException
     */
    public function get(string $id): object
    {
        /** Ja construiu antes? devolve o mesmo objeto */
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        /** Tem receita? Executa */
        if (isset($this->bindings[$id])) {
            $object = $this->bindings[$id]();

            /** Se for Singleton, guarda para a proxima vez */
            if (isset($this->singletons[$id])) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        /** Lógica para injeção de depedencia de forma automatica */
        $reflector = new ReflectionClass($id);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $id();
        }

        $parameters = [];

        foreach ($constructor->getParameters() as $param) {
            $parameters[] = $this->resolve($param, $id);
        }

        return $reflector->newInstanceArgs($parameters);
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = true;
    }

    /**
     * Reflexão só resolve dependência que é classe. Tipo primitivo (string, int),
     * parâmetro sem tipo ou union type precisam de um bind explícito — sem esta
     * checagem o erro sairia como "Class string does not exist", que não ajuda ninguém.
     */
    private function resolve(ReflectionParameter $param, string $id): mixed
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException(sprintf(
            'Não consegui resolver $%s de %s automaticamente. Registre um bind para essa classe.',
            $param->getName(),
            $id,
        ));
    }
}
