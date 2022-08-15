<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Injector.php - Part of the container project.

  © - Jitesoft
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Class Injector
 *
 * Handles dependency injection through constructors.
 *
 * @internal
 */
class Injector {
    protected ?ContainerInterface $container;

    /**
     * Injector constructor.
     *
     * @param ContainerInterface|null $container Container to use for resolving in case one exist.
     *
     * @internal
     */
    public function __construct(?ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * Create a instance of given class name.
     * The injector will try to handle constructor injection, if it fails, it will throw an exception.
     *
     * If a binding array is passed through this method and a container already exists, the bindings will take
     * precedence over the container.
     *
     * @param string $className Name of the class to create.
     * @param array  $bindings  Key value bindings list. Not required if a container exists.
     *
     * @throws ContainerException|ContainerExceptionInterface Thrown if the container fails to create class.
     * @throws NotFoundException|NotFoundExceptionInterface Thrown if type hint was not found.
     * @throws ReflectionException Thrown if instantiation failed.
     *
     * @internal
     */
    public function create(string $className, array $bindings = []): object {
        try {
            $class = new ReflectionClass($className);
        } catch (ReflectionException $ex) {
            throw new ContainerException(
                'Failed to create reflection class from given class name.'
            );
        }

        // Does the class have a constructor?
        if ($class->getConstructor() !== null) {
            $ctr = $class->getConstructor();

            if ($ctr === null) {
                throw new ContainerException(
                    sprintf(
                        'Class with name %s does not have a constructor.',
                        $className
                    )
                );
            }

            try {
                $params = $ctr->getParameters();
            } catch (ReflectionException $ex) {
                throw new ContainerException(
                    sprintf(
                        'Failed to fetch parameters from constructor from %s',
                        $className
                    ),
                    $ex->getCode(),
                    $ex
                );
            }

            // Create the new class from the parameters.
            return $class->newInstanceArgs(
                $this->getParameters($params, $bindings)
            );
        }

        // No constructor, so just return a new instance.
        return $class->newInstanceWithoutConstructor();
    }

    /**
     * Call the callable passed as first argument with resolved bindings depending.
     * The injector will try to use the dependency container to resolve the parameters of the
     * function, and will throw a ContainerException in case it fails.
     *
     * If a binding array is passed through this method and a container already exists, the bindings will take
     * precedence over the container.
     *
     * @throws ContainerException|ContainerExceptionInterface
     * @throws NotFoundException|NotFoundExceptionInterface
     */
    public function invoke(callable $callable,  array $bindings = []): mixed {
        try {
            $func = new ReflectionFunction($callable);
        } catch (ReflectionException $ex) {
            throw new ContainerException(
                'Failed to create reflection function from given callable.'
            );
        }

        $params = $func->getParameters();
        return $func->invoke(...$this->getParameters($params, $bindings));
    }

    /**
     * @param ReflectionParameter[]|array $params   List of parameters.
     * @param array                       $bindings List of bindings to use when creating objects for parameters.
     *
     * @return array List of resolved parameters.
     *
     * @throws ContainerException|ContainerExceptionInterface Thrown if the container fails to create class.
     * @throws NotFoundException|NotFoundExceptionInterface Thrown if type hint was not found.
     */
    private function getParameters(array $params, array $bindings): array {
        // Get all the parameters that the class require, if any.
        return array_map(
            function ($param) use ($bindings) {
                $type = $this->getTypeHint($param);
                if (array_key_exists($type, $bindings)) {
                    return $bindings[$type];
                }

                if ($this->container->has($type)) {
                    return $this->container->get($type);
                }

                try {
                    return $this->create($type, $bindings);
                } catch (ReflectionException $e) {
                    throw new ContainerException('Failed to create class.');
                }
            }, $params
        );
    }

    /**
     * @param ReflectionParameter $param Reflection Parameter to get class name from.
     *
     * @return string Name of the parameter type.
     *
     * @throws NotFoundException Thrown if type hint was not found.
     */
    private function getTypeHint(ReflectionParameter $param): string {
        $type = $param->getType();
        if (!($type instanceof ReflectionNamedType)) {
            // @noinspection ProperNullCoalescingOperatorUsageInspection
            throw new NotFoundException(
                sprintf(
                    'Failed to resolve type for parameter %s (type %s).',
                    $param->getName(),
                    $type ?? 'null'
                )
            );
        }

        return $type->getName();
    }

}
