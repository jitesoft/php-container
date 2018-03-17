<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Container.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use ArrayAccess;
use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Simple naive implementation of a Dependency container with constructor injection.
 */
class Container implements ContainerInterface, ArrayAccess {

    protected $instances = [];
    protected $container = [];
    protected $injector;

    /**
     * Container constructor.
     *
     * @param array $bindings - Container bindings.
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(array $bindings = []) {
        foreach ($bindings as $abstract => $concrete) {
            $this->set($abstract, $concrete);
        }

        $this->injector = new Injector($this);
    }

    /**
     * Clear the container.
     */
    public function clear() {
        $this->instances = [];
        $this->container = [];
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
            $this->container[$abstract] = $abstract;
            $this->instances[$abstract] = $concrete;
            return true;
        }

        $this->container[$abstract] = $concrete;
        return true;
    }

    /**
     * @param string $abstract
     * @param $concrete
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function rebind(string $abstract, $concrete) {
        $this->unset($abstract);
        $this->set($abstract, $concrete);
    }

    /**
     * @param string $id
     * @throws NotFoundExceptionInterface
     */
    public function unset(string $id) {
        if (!$this->has($id)) {
            throw new NotFoundException("Could not remove the given entity because it was not set.");
        }

        unset($this->container[$id]);
        unset($this->instances[$id]);
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
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        } else if (array_key_exists($id, $this->container)) {
            return $this->injector->create($this->container[$id]);
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
        return array_key_exists($id, $this->container);
    }

    public function offsetExists($offset) {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws ContainerExceptionInterface
     */
    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @throws NotFoundExceptionInterface
     */
    public function offsetUnset($offset) {
        $this->unset($offset);
    }

}
