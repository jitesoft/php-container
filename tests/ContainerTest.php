<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerTest.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

use Jitesoft\Container\Container;
use Jitesoft\Container\Exceptions\ContainerException;
use Jitesoft\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

    /** @var Container */
    protected $container;

    protected function setUp() {
        parent::setUp();
        $this->container = new Container();
    }

    protected function tearDown() {
        parent::tearDown();
        $this->container->clear();
    }

    public function testConstructorWithParams() {
        $container = new Container([
            'a' => 'b',
            TestInterface_C::class => TestClass_C::class
        ]);

        $this->assertEquals('b', $container->get('a'));
        $this->assertInstanceOf(TestClass_C::class, $container->get(TestInterface_C::class));
    }

    public function testSetPrimitiveValue() {
        $this->assertTrue($this->container->set("Value", 123));
        $this->assertTrue($this->container->set("Value2", 1234));
        $this->assertTrue($this->container->set("Value3", 12345));
    }

    public function testSetClassName() {
        $this->assertTrue($this->container->set(TestInterface_A::class, TestClass_C::class));
        $this->assertTrue($this->container->set(TestInterface_B::class, TestClass_B::class));
        $this->assertTrue($this->container->set(TestInterface_C::class, TestClass_A::class));
    }

    public function testSetObject() {
        $this->assertTrue($this->container->set(TestInterface_A::class, new class {

        }));
        $this->assertTrue($this->container->set(TestInterface_B::class, new class {

        }));
        $this->assertTrue($this->container->set(TestInterface_C::class, new class {

        }));
    }

    public function testSetDuplicatedId() {
        $this->assertTrue($this->container->set(TestInterface_A::class, new class {
        }));
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf('An entry with the id "%s" already exists.', TestInterface_A::class)
        );
        $this->container->set(TestInterface_A::class, new class {
        });
    }

    public function testGetPrimitiveValue() {
        $this->container->set('A', 123);
        $this->container->set('B', 321);

        $this->assertEquals(123, $this->container->get('A'));
        $this->assertEquals(321, $this->container->get('B'));
    }

    public function testGetNewObject() {
        $this->container->set(TestInterface_A::class, TestClass_C::class);
        $this->container->set("Another", TestClass_C::class);

        $instanceOne   = $this->container->get(TestInterface_A::class);
        $instanceTwo   = $this->container->get(TestInterface_A::class);
        $instanceThree = $this->container->get('Another');

        $this->assertInstanceOf(TestClass_C::class, $instanceOne);
        $this->assertInstanceOf(TestClass_C::class, $instanceTwo);
        $this->assertInstanceOf(TestClass_C::class, $instanceThree);

        $this->assertNotEquals($instanceThree->id, $instanceTwo->id);
        $this->assertNotEquals($instanceThree->id, $instanceOne->id);
    }

    public function testGetSingletonObject() {
        $this->container->set(TestInterface_A::class, new TestClass_C());

        $instanceOne = $this->container->get(TestInterface_A::class);
        $instanceTwo = $this->container->get(TestInterface_A::class);

        $this->assertInstanceOf(TestClass_C::class, $instanceOne);
        $this->assertInstanceOf(TestClass_C::class, $instanceTwo);

        $this->assertSame($instanceOne->id, $instanceTwo->id);
    }

    public function testGetWithConstructorInjection() {
        $this->container->set(TestInterface_C::class, TestClass_C::class);
        $this->container->set(TestInterface_B::class, TestClass_B::class);
        $this->container->set(TestInterface_A::class, TestClass_A::class);

        /** @var TestClass_A $object */
        $object = $this->container->get(TestInterface_A::class);
        $this->assertInstanceOf(TestClass_A::class, $object);
        $this->assertInstanceOf(TestClass_B::class, $object->obj);
        $this->assertInstanceOf(TestClass_C::class, $object->obj->obj);
        $this->assertEquals(TestClass_C::$counter, $object->obj->obj->id);
    }

    public function testGetInvalidId() {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Could not locate an entry in the container with the id "%s".', TestInterface_A::class)
        );
        $this->container->get(TestInterface_A::class);
    }

    public function testGetFailed() {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->expectException(ContainerException::class);
        // When fetching the value, it will break on the constructor injection and throw an exception.
        $this->expectExceptionMessage(sprintf(
            'Could not locate an entry in the container with the id "%s".',
            TestInterface_B::class
        ));
        $this->container->get(TestInterface_A::class);
    }

    public function testHasPrimitiveValue() {
        $this->container->set('A', 123);
        $this->assertTrue($this->container->has('A'));
    }

    public function testHasClassName() {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->assertTrue($this->container->has(TestInterface_A::class));
    }

    public function testHasObject() {
        $this->container->set(TestInterface_C::class, new TestClass_C());
        $this->assertTrue($this->container->has(TestInterface_C::class));
    }

    public function testHasFail() {
        $this->assertFalse($this->container->has('ABCD'));
    }

    public function testUnset() {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->assertTrue($this->container->has(TestInterface_A::class));
        $this->container->unset(TestInterface_A::class);
        $this->assertFalse($this->container->has(TestInterface_A::class));
    }

    public function testRebind() {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->assertTrue($this->container->has(TestInterface_A::class));
        $this->container->unset(TestInterface_A::class);
        $this->assertFalse($this->container->has(TestInterface_A::class));
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->assertTrue($this->container->has(TestInterface_A::class));
    }

}
