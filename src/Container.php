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
use ReflectionException;
use ReflectionParameter;

/**
 * Simple naive implementation of a Dependency container with constructor injection.
 */
class Container implements ContainerInterface, ArrayAccess {

    protected $instances = [];
    protected $container = [];

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
     * @throws NotFoundException
     */
    public function rebind(string $abstract, $concrete) {
        $this->unset($abstract);
        $this->set($abstract, $concrete);
    }

    /**
     * @param string $id
     * @throws NotFoundException
     */
    public function unset(string $id) {
        if (!$this->has($id)) {
            throw new NotFoundException("Could not remove the given entity because it was not set.");
        }

        unset($this->container[$id]);
        unset($this->instances[$id]);
    }

    /**
     * @param ReflectionParameter $param
     * @return string
     * @throws NotFoundException
     */
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

    /**
     * @param $className
     * @return null|object
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
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
                } catch (NotFoundExceptionInterface $ex) {
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
            try {
                return $this->createObject($this->container[$id]);
            } catch (ReflectionException $ex) {
                throw new ContainerException('Failed to get value from container.');
            }
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
     * @throws NotFoundException
     */
    public function offsetUnset($offset) {
        $this->unset($offset);
    }

    // endregion

}
