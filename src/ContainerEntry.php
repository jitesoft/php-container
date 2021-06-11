<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerEntry.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Jitesoft\Exceptions\Psr\Container\ContainerException;
use Jitesoft\Exceptions\Psr\Container\NotFoundException;
use ReflectionFunction;

/**
 * Class ContainerEntry
 *
 * @internal
 */
class ContainerEntry {
    /** @var mixed */
    protected $concrete;
    protected bool $isSingleton;
    protected bool $isCreated;
    protected string $abstract;

    /**
     * ContainerEntry constructor.
     *
     * @param string  $abstract    Abstract to bind.
     * @param mixed   $concrete    Concrete to bind to abstract.
     * @param boolean $isSingleton If a singleton instance or not.
     *
     * @internal
     */
    public function __construct(string $abstract,
                                mixed $concrete,
                                bool $isSingleton = false) {
        $this->abstract    = $abstract;
        $this->concrete    = $concrete;
        $this->isCreated   = (
            (!is_string($concrete) || !class_exists($concrete))
        );
        $this->isSingleton = $isSingleton;
    }

    /**
     * @param Injector $injector Injector to use for resolving.
     *
     * @return mixed
     *
     * @throws ContainerException Thrown in case the container fails in injection.
     * @throws NotFoundException Thrown in case the value is not found.
     * @internal
     */
    public function resolve(Injector $injector): mixed {
        if ($this->isCreated) {
            return $this->concrete;
        }

        if (class_exists($this->concrete)) {
            $object = $injector->create($this->concrete);
            if ($this->isSingleton) {
                $this->isCreated = true;
                $this->concrete  = $object;
            }
        }


        if (is_callable($this->concrete))  {
            $object = $injector->invoke($this->concrete);
            if ($this->isSingleton) {
                $this->isCreated = true;
                $this->concrete = $object;
            }

            return $object;
        }



        return $object;
    }

}
