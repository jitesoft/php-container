<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerFactoryTest.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

use Jitesoft\Container\Container;
use Jitesoft\Container\ContainerFactory;
use Jitesoft\Container\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerFactoryTest extends TestCase {

    /** @var Container */
    protected $container;

    protected function setUp() {
        parent::setUp();
        $this->container = ContainerFactory::create('container');
    }

    protected function tearDown() {
        parent::tearDown();
        ContainerFactory::remove('container');
    }

    public function testCreateContainer() {
        $container = ContainerFactory::create('Test');
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertInstanceOf(Container::class, $container);
        ContainerFactory::remove('Test');
    }

    public function testCreateContainerExists() {
        ContainerFactory::create('Test');
        try {
            ContainerFactory::create('Test');
        } catch (ContainerException $ex) {
            $this->assertEquals(
                'Invalid identifier. A container with the identifier Test already exist.',
                $ex->getMessage()
            );
        }

        ContainerFactory::remove('Test');
    }

    public function testGetExistingContainer() {
        $this->assertSame($this->container, ContainerFactory::get('container'));
    }

    public function testGetNewContainer() {
        $this->assertNotSame($this->container, ContainerFactory::create('Test'));
        ContainerFactory::remove('Test');
    }

}
