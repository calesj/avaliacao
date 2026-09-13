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
    public function get(string $class): object
    {
        /** Ja construiu antes? devolve o mesmo objeto */
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        /** Tem receita? Executa */
        if (isset($this->bindings[$class])) {
            $object = $this->bindings[$class]();

            /** Se for Singleton, guarda para a proxima vez */
            if (isset($this->singletons[$class])) {
                $this->instances[$class] = $object;
            }

            return $object;
        }

        /** Lógica para injeção de depedencia de forma automatica */
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters = [];

        foreach ($constructor->getParameters() as $param) {
            $parameters[] = $this->resolve($param, $class);
        }

        return $reflector->newInstanceArgs($parameters);
    }

    public function singleton(string $class, callable $factory): void
    {
        $this->bindings[$class] = $factory;
        $this->singletons[$class] = true;
    }

    /**
     * Reflexão só resolve dependência que é classe. Tipo primitivo (string, int),
     * parâmetro sem tipo ou union type precisam de um bind explícito — sem esta
     * checagem o erro sairia como "Class string does not exist", que não ajuda ninguém.
     */
    private function resolve(ReflectionParameter $param, string $class): mixed
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
            $class,
        ));
    }
}
