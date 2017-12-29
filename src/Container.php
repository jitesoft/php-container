<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  Container.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use ArrayAccess;
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
class Container implements ContainerInterface, ArrayAccess {

    // region Static functions.

    private static function getContainers(): Container {
        static $containers = null;

        if ($containers === null) {
            $containers = new Container();
        }

        return $containers;
    }

    /**
     * Fetch a container.
     * If the container is not yet named, a new one will be created without any bindings.
     *
     * @param string $identifier  - The unique identifier of the container.
     * @return ContainerInterface - The container requested.
     */
    public static function getContainer($identifier = 'default'): ContainerInterface {
        $container = self::getContainers();

        if (!$container->has($identifier)) {
            return self::createContainer($identifier);
        }

        return $container[$identifier];
    }

    /**
     * Create a new container with given unique identifier.
     *
     * @param string                  $identifier - Unique identifier to use for the container.
     * @param array                   $bindings   - Bindings, defaults to no bindings.
     * @param ContainerInterface|null $container  - A container (implementing PSR-11 container interface) to use.
     *
     * @return ContainerInterface The newly created container.
     *
     * @throws ContainerException If the unique identifier is already used.
     */
    public static function createContainer(string $identifier,
                                            array $bindings = [],
                                            ContainerInterface $container = null): ContainerInterface {
        $c = self::getContainers();

        $newContainer = (null === $container ? new Container($bindings) : $container);

        $c[$identifier] = $newContainer;
        return $c[$identifier];
    }

    /**
     * Remove a given container.
     *
     * @param string $identifier - Container identifier.
     */
    public static function removeContainer(string $identifier) {
        $container = self::getContainers();
        $container->unset($identifier);
    }

    // endregion

    protected $instances = [];
    protected $container = [];

    /**
     * Container constructor.
     *
     * @param array $bindings - Container bindings.
     *
     * @throws ContainerException
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

    public function unset(string $id) {
        if (!$this->has($id)) {
            throw new NotFoundException("Could not remove the given entity because it was not set.");
        }

        unset($this->container[$id]);
        unset($this->instances[$id]);
    }

    private function getTypeHint(ReflectionParameter $param) {
        if ($param->getClass()) {
            return $param->getClass()->getName();
        }

        throw new NotFoundException(sprintf(
                'Constructor parameter "%s" could not be created.',
                $param->getName()
            )
        );
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
                try {
                    $get = $this->get($type);
                } catch (NotFoundException $ex) {
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
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        } else if (array_key_exists($id, $this->container)) {
            return $this->createObject($this->container[$id]);
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

    // region ArrayAccess implementation.

    public function offsetExists($offset) {
        return $this->has($offset);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->unset($offset);
    }

    // endregion

}
