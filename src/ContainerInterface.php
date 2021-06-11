<?php

namespace Jitesoft\Container;

use ArrayAccess;
use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface, ArrayAccess {
    /**
     * Clear the container.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Bind an abstract to a concrete.
     *
     * If the concrete is a object and not a string, it will be stored as it is.
     * If the concrete is a callable, the callable will be invoked (and resolved) on resolve.
     *
     * @param string  $abstract  Abstract value to bind the concrete value to.
     * @param mixed   $concrete  Concrete value to bind to the abstract value.
     * @param boolean $singleton If the created object is intended to be treated as a single instance on creation.
     *
     * @return boolean
     * @throws ContainerException Thrown in case the entry already exist.
     */
    public function set(string $abstract, mixed $concrete, bool $singleton = false): bool;

    /**
     * Re-bind a value to a given abstract.
     * This will remove the earlier entry and set a new one.
     *
     * @param string  $abstract  Abstract value to bind the concrete value to.
     * @param mixed   $concrete  Concrete value to bind to the abstract value.
     * @param boolean $singleton If the created object is intended to be treated as a single instance on creation.
     *
     * @throws NotFoundException Thrown in case the 'abstract' does not exist.
     *
     * @return void
     */
    public function rebind(string $abstract, mixed $concrete, bool $singleton = false): void;

    /**
     * Unset a given abstract removing it from the container.
     *
     * @param string $id Identifier of the value to remove entry for.
     *
     * @throws NotFoundException Thrown if the abstract is not found.
     *
     * @return void
     */
    public function unset(string $id): void;

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
     * @throws ContainerException Thrown in case the entry already exist.
     */
    public function singleton(string $abstract, mixed $concrete): bool;
}
