<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Container.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Container\Exceptions\ContainerException;
use Jitesoft\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionParameter;

/**
 * Simple naive implementation of a Dependency container with constructor injection.
 */
class Container implements ContainerInterface {

    protected static $instances = [];
    protected static $container = [];

    /**
     * Container constructor.
     * @param array $bindings
     */
    public function __construct(array $bindings = []) {
        foreach ($bindings as $abstract => $concrete) {
            $this->set($abstract, $concrete);
        }
    }

    /**
     * Clear the container.
     */
    public function clear() {
        self::$instances = [];
        self::$container = [];
    }

    /**
     * Bind a abstract to a concrete.
     * If the concrete is a object and not a string, it will be stored as it is.
     *
     * @param string $abstract
     * @param object|string $concrete
     *
     * @throws ContainerExceptionInterface
     *
     * @return bool
     */
    public function set(string $abstract, $concrete) {
        if ($this->has($abstract)) {
            throw new ContainerException(
                sprintf('An entry with the id "%s" already exists.', $abstract)
            );
        }

        if (!is_string($concrete) || !class_exists($concrete)) {
            self::$container[$abstract] = $abstract;
            self::$instances[$abstract] = $concrete;
            return true;
        }

        self::$container[$abstract] = $concrete;
        return true;
    }

    public function unset(string $id) {
        if (!$this->has($id)) {
            return;
        }

        unset(self::$container[$id]);
        unset(self::$instances[$id]);
    }

    private function getTypeHint(ReflectionParameter $param) {
        if ($param->getClass()) {
            return $param->getClass()->getName();
        }

        return null;
    }

    private function createObject($className) {
        $class = new ReflectionClass($className);
        $out   = null;

        if ($class->getConstructor() !== null) {
            $ctr    = $class->getConstructor();
            $params = $ctr->getParameters();

            $inParam = [];
            foreach ($params as $param) {
                $type = $this->getTypeHint($param);
                $get  = $this->get($type);

                if ($get === null) {
                    $get = $this->createObject($type);
                }

                $inParam[] = $get;
            }

            $out = $class->newInstanceArgs($inParam);
        } else {
            $out = $class->newInstanceWithoutConstructor();
        }

        return $out;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id) {
        if (array_key_exists($id, self::$instances)) {
            return self::$instances[$id];
        }

        if (array_key_exists($id, self::$container)) {
            if (array_key_exists(self::$container[$id], self::$instances)) {
                return self::$instances[self::$container[$id]];
            }

            return $this->createObject(self::$container[$id]);
        }

        throw new NotFoundException(
            sprintf('Could not locate an entry in the container with the id "%s".', $id)
        );
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id) {
        return array_key_exists($id, self::$container);
    }

}
