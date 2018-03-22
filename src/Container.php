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

    /** @var array|ContainerEntry[] */
    protected $bindings = [];

    /**
     * Container constructor.
     *
     * @param array $bindings - Container bindings.
     *
     * @throws ContainerException
     */
    public function __construct(array $bindings = []) {
        foreach ($bindings as $abstract => $concrete) {
            $singleton = false;
            if (is_array($concrete)) {
                $singleton = $concrete['singleton'];
                $concrete  = $concrete['class'];
            }

            $this->set($abstract, $concrete, $singleton);
        }
    }

    /**
     * Clear the container.
     */
    public function clear() {
        $this->bindings = [];
    }

    /**
     * Bind a abstract to a concrete.
     * If the concrete is a object and not a string, it will be stored as it is.
     *
     * @param string $abstract
     * @param object|string $concrete
     * @param bool $singleton If the created object is intended to be treated as a single instance on creation.
     *
     * @return bool
     * @throws ContainerException
     */
    public function set(string $abstract, $concrete, $singleton = false) {
        if ($this->has($abstract)) {
            throw new ContainerException(
                sprintf('An entry with the id "%s" already exists.', $abstract)
            );
        }

        $this->bindings[$abstract] = new ContainerEntry($abstract, $concrete, $singleton);
        return true;
    }

    /**
     * @param string $abstract
     * @param $concrete
     * @param bool $singleton
     *
     * @throws ContainerException
     * @throws NotFoundExceptionInterface
     */
    public function rebind(string $abstract, $concrete, $singleton = false) {
        $this->unset($abstract);
        $this->set($abstract, $concrete, $singleton);
    }

    /**
     * @param string $abstract
     * @throws NotFoundExceptionInterface
     */
    public function unset(string $abstract) {
        if (!$this->has($abstract)) {
            throw new NotFoundException("Could not remove the given entity because it was not set.");
        }

        unset($this->bindings[$abstract]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $abstract Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($abstract) {
        if (array_key_exists($abstract, $this->bindings)) {
            return $this->bindings[$abstract]->resolve(new Injector($this));
        }

        throw new NotFoundException(
            sprintf('Could not locate an entry in the container with the id "%s".', $abstract)
        );
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $abstract Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($abstract) {
        return array_key_exists($abstract, $this->bindings);
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
