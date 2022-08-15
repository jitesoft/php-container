<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Container.php - Part of the container project.

  © - Jitesoft
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Simple naive implementation of a Dependency container with constructor injection.
 */
class Container implements ContainerInterface {
    /** @var array|ContainerEntry[] */
    protected array $bindings = [];

    /**
     * Container constructor.
     *
     * @param array $bindings Container bindings.
     */
    public function __construct(array $bindings = []) {
        foreach ($bindings as $abstract => $concrete) {
            $singleton = false;
            if (is_array($concrete)) {
                $singleton = $concrete['singleton'];
                $concrete  = $concrete['class'];
            }

            try {
                $this->set($abstract, $concrete, $singleton);
            } catch (ContainerException $ex) {
                // This should not be able to happen.
                return;
            }
        }
    }

    /**
     * Clear the container.
     *
     * @return void
     */
    public function clear(): void {
        $this->bindings = [];
    }

    /**
     * Bind a abstract to a concrete.
     * If the concrete is a object and not a string, it will be stored as it is.
     *
     * @param string  $abstract  Abstract value to bind the concrete value to.
     * @param mixed   $concrete  Concrete value to bind to the abstract value.
     * @param boolean $singleton If the created object is intended to be treated as a single instance on creation.
     *
     * @return boolean
     * @throws ContainerException|ContainerExceptionInterface Thrown in case the entry already exist.
     */
    public function set(string $abstract,
                        mixed $concrete,
                        bool $singleton = false): bool {
        if ($this->has($abstract)) {
            throw new ContainerException(
                sprintf('An entry with the id "%s" already exists.', $abstract)
            );
        }

        $this->bindings[$abstract] = new ContainerEntry(
            $abstract,
            $concrete,
            $singleton
        );

        return true;
    }

    /**
     * Re-bind a value to a given abstract.
     * This will remove the earlier entry and set a new one.
     *
     * @param string  $abstract  Abstract value to bind the concrete value to.
     * @param mixed   $concrete  Concrete value to bind to the abstract value.
     * @param boolean $singleton If the created object is intended to be treated as a single instance on creation.
     *
     * @throws NotFoundException|NotFoundExceptionInterface Thrown in case the 'abstract' does not exist.
     * @throws ContainerException|ContainerExceptionInterface Thrown in case entry could not be set.
     *
     * @return void
     */
    public function rebind(string $abstract,
                           mixed $concrete,
                           bool $singleton = false): void {
        $this->unset($abstract);
        try {
            $this->set($abstract, $concrete, $singleton);
        } catch (ContainerException $exception) {
            // Should not be possible to happen.
            return;
        }
    }

    /**
     * Unset a given abstract removing it from the container.
     *
     * @param string|mixed $id Identifier of the value to remove entry for.
     *
     * @throws NotFoundException|NotFoundExceptionInterface Thrown if the abstract is not found.
     *
     * @return void
     */
    public function unset(mixed $id): void {
        if (!$this->has($id)) {
            throw new NotFoundException(
                'Could not remove the given entity because it was not set.'
            );
        }

        unset($this->bindings[$id]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string|mixed $id Identifier of the entry to look for.
     *
     * @throws NotFoundException|NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerException|ContainerExceptionInterface On resolve error.
     *
     * @return mixed Entry.
     */
    public function get(mixed $id): mixed {
        if (array_key_exists($id, $this->bindings)) {
            return $this->bindings[$id]->resolve(new Injector($this));
        }

        throw new NotFoundException(
            sprintf(
                'Could not locate an entry in the container with the id "%s".',
                $id
            )
        );
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string|mixed $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has(mixed $id): bool {
        return array_key_exists($id, $this->bindings);
    }

    /**
     * @param string|mixed $offset Offset to check for.
     *
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool {
        return $this->has($offset);
    }

    /**
     * @param string|mixed $offset Offset to fetch.
     *
     * @throws NotFoundException|NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerException|ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->get($offset);
    }

    /**
     * @param string|mixed $offset Offset to set.
     * @param mixed        $value  Value to set to the offset.
     *
     * @throws ContainerException|ContainerExceptionInterface Thrown if offset does not exist.
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->set($offset, $value);
    }

    /**
     * @param string|mixed $offset Offset to unset
     *
     * @throws NotFoundException|NotFoundExceptionInterface Thrown if offset does not exist.
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void {
        $this->unset($offset);
    }

    /**
     * Binds an abstract to a concrete.
     *
     * If the concrete is a object and not a string, it will be stored as it is.
     * If the concrete is a callable, the callable will be invoked (and resolved) on resolve.
     *
     * @param string $abstract  Abstract value to bind the concrete value to.
     * @param mixed  $concrete  Concrete value to bind to the abstract value.
     *
     * @return boolean
     * @throws ContainerException|ContainerExceptionInterface Thrown in case the entry already exist.
     */
    public function singleton(string $abstract, mixed $concrete): bool {
        return $this->set($abstract, $concrete, true);
    }

}
