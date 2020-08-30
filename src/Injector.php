<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Injector.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Class Injector
 *
 * Handles dependency injection through constructors.
 *
 * @internal
 */
class Injector {

    /** @var ContainerInterface|null */
    protected $container;

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
     * @throws ContainerException Thrown if the container fails to create class.
     *
     * @return null|object
     *
     * @internal
     */
    public function create(string $className, array $bindings = []) {
        try {
            $class = new ReflectionClass($className);
        } catch (ReflectionException $ex) {
            throw new ContainerException(
                'Failed to create reflection class from given class name.'
            );
        }

        if ($class->getConstructor() !== null) {
            $ctr     = $class->getConstructor();
            $params  = $ctr->getParameters();

            // Get all the parameters that the class require, if any.
            $inParam = array_map(function ($param) use($bindings) {
                $type = $this->getTypeHint($param);
                if (array_key_exists($type, $bindings)) {
                    return $bindings[$type];
                }

                if ($this->container->has($type)) {
                    return $this->container->get($type);
                }

                return $this->create($type, $bindings);
            }, $params);

            // Create the new class from the parameters.
            return $class->newInstanceArgs($inParam);
        }
        // No constructor, so just return a new instance.
        return $class->newInstanceWithoutConstructor();
    }

    /**
     * @param ReflectionParameter $param Reflection Parameter to get class name from.
     *
     * @throws NotFoundException Thrown if type hint was not found.
     *
     * @return string
     */
    private function getTypeHint(ReflectionParameter $param): string {
        if ($param->getClass()) {
            return $param->getClass()->getName();
        }

        throw new NotFoundException(
            sprintf(
                'Constructor parameter "%s" could not be created.',
                $param->getName()
            )
        );
    }

}
