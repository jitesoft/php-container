<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerFactory.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerFactory {

    protected static $containers = [];

    /**
     * Create a new Container.
     *
     * @param string $identifier
     * @param array $bindings
     * @param bool $define If true, creates a define for the given identifier.
     * @return Container
     * @throws ContainerException
     * @throws ContainerExceptionInterface If the identifier already exist.
     */
    public static function create(string $identifier, array $bindings = [], bool $define = false): Container {
        if (self::has($identifier)) {
            throw new ContainerException(
                sprintf('Invalid identifier. A container with the identifier %s already exist.', $identifier)
            );
        }

        self::$containers[$identifier] = new Container($bindings);
        return self::$containers[$identifier];
    }

    /**
     * Check if a given container exist.
     *
     * @param string $identifier
     * @return bool
     */
    public static function has(string $identifier): bool {
        return array_key_exists($identifier, self::$containers);
    }

    /**
     * Remove a container and all its bindings.
     *
     * @param string $identifier
     * @return bool
     * @throws ContainerExceptionInterface
     */
    public static function remove(string $identifier): bool {
        if (!self::has($identifier)) {
            throw new NotFoundException(sprintf('Could not find a Container with the identifier %s.', $identifier));
        }

        unset(self::$containers[$identifier]);
        return true;
    }

    /**
     * Remove all containers.
     * This will only remove the containers from the ContainerFactory, any
     * current references to given containers will remain.
     */
    public static function clear() {
        self::$containers = [];
    }

    /**
     * Get an existing container.
     *
     * @param string $identifier
     * @return Container
     * @throws NotFoundExceptionInterface
     */
    public static function get(string $identifier): Container {
        if (!self::has($identifier)) {
            throw new NotFoundException(sprintf('Could not find a Container with the identifier %s.', $identifier));
        }

        return self::$containers[$identifier];
    }

    /**
     * Get all containers.
     *
     * @return array|Container[]
     */
    public static function all(): array {
        return self::$containers;
    }

}
