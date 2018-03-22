<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerEntry.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ContainerEntry
 * @internal
 */
class ContainerEntry {

    /** @var string */
    protected $abstract;
    /** @var mixed */
    protected $concrete;
    /** @var bool */
    protected $isSingleton;
    /** @var bool */
    protected $isCreated;

    /**
     * ContainerEntry constructor.
     *
     * @param string $abstract
     * @param $concrete
     * @param bool $isSingleton
     *
     * @internal
     */
    public function __construct(string $abstract, $concrete, bool $isSingleton = false) {
        $this->abstract    = $abstract;
        $this->concrete    = $concrete;
        $this->isCreated   = (!is_string($concrete) || !class_exists($concrete));
        $this->isSingleton = $isSingleton;
    }

    /**
     * @param Injector $injector
     * @return mixed|null|object|string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @internal
     */
    public function resolve(Injector $injector) {
        if ($this->isCreated) {
            return $this->concrete;
        }

        $object = $injector->create($this->concrete);
        if ($this->isSingleton) {
            $this->isCreated = true;
            $this->concrete  = $object;
        }

        return $object;
    }

}
