<?php /** @noinspection PhpUnhandledExceptionInspection */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  ContainerTest.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

use Jitesoft\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerTest extends TestCase {
    protected Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
    }

    public function testConstructorWithParams(): void {
        $container = new Container([
            'a' => 'b',
            TestInterface_C::class => TestClass_C::class
        ]);

        self::assertEquals('b', $container->get('a'));
        self::assertInstanceOf(TestClass_C::class, $container->get(TestInterface_C::class));
    }

    public function testConstructorWithSingletonParam(): void {
        $container = new Container([
           TestInterface_C::class => [
               'singleton' => true,
               'class' => TestClass_C::class
           ]
        ]);

        $out = $container->get(TestInterface_C::class);
        self::assertInstanceOf(TestClass_C::class, $out);
        self::assertSame($out, $container->get(TestInterface_C::class));
    }

    public function testSetPrimitiveValue(): void {
        self::assertTrue($this->container->set("Value", 123));
        self::assertTrue($this->container->set("Value2", 1234));
        self::assertTrue($this->container->set("Value3", 12345));
    }

    public function testSetClassName(): void {
        self::assertTrue($this->container->set(TestInterface_A::class, TestClass_C::class));
        self::assertTrue($this->container->set(TestInterface_B::class, TestClass_B::class));
        self::assertTrue($this->container->set(TestInterface_C::class, TestClass_A::class));
    }

    public function testSetObject(): void {
        self::assertTrue($this->container->set(TestInterface_A::class, new class {

        }));
        self::assertTrue($this->container->set(TestInterface_B::class, new class {

        }));
        self::assertTrue($this->container->set(TestInterface_C::class, new class {

        }));
    }

    public function testSetDuplicatedId(): void {
        self::assertTrue($this->container->set(TestInterface_A::class, new class {
        }));
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(
            sprintf('An entry with the id "%s" already exists.', TestInterface_A::class)
        );
        $this->container->set(TestInterface_A::class, new class {
        });
    }

    public function testGetPrimitiveValue(): void {
        $this->container->set('A', 123);
        $this->container->set('B', 321);

        self::assertEquals(123, $this->container->get('A'));
        self::assertEquals(321, $this->container->get('B'));
    }

    public function testGetNewObject(): void {
        $this->container->set(TestInterface_A::class, TestClass_C::class);
        $this->container->set("Another", TestClass_C::class);

        $instanceOne   = $this->container->get(TestInterface_A::class);
        $instanceTwo   = $this->container->get(TestInterface_A::class);
        $instanceThree = $this->container->get('Another');

        self::assertInstanceOf(TestClass_C::class, $instanceOne);
        self::assertInstanceOf(TestClass_C::class, $instanceTwo);
        self::assertInstanceOf(TestClass_C::class, $instanceThree);

        self::assertNotEquals($instanceThree->id, $instanceTwo->id);
        self::assertNotEquals($instanceThree->id, $instanceOne->id);
    }

    public function testGetSingletonObject(): void {
        $this->container->set(TestInterface_A::class, new TestClass_C());

        $instanceOne = $this->container->get(TestInterface_A::class);
        $instanceTwo = $this->container->get(TestInterface_A::class);

        self::assertInstanceOf(TestClass_C::class, $instanceOne);
        self::assertInstanceOf(TestClass_C::class, $instanceTwo);

        self::assertSame($instanceOne->id, $instanceTwo->id);
    }

    public function testGetWithConstructorInjection(): void {
        $this->container->set(TestInterface_C::class, TestClass_C::class);
        $this->container->set(TestInterface_B::class, TestClass_B::class);
        $this->container->set(TestInterface_A::class, TestClass_A::class);

        /** @var TestClass_A $object */
        $object = $this->container->get(TestInterface_A::class);
        self::assertInstanceOf(TestClass_A::class, $object);
        self::assertInstanceOf(TestClass_B::class, $object->obj);
        self::assertInstanceOf(TestClass_C::class, $object->obj->obj);
        self::assertEquals(TestClass_C::$counter, $object->obj->obj->id);
    }

    public function testGetInvalidId(): void {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage(
            sprintf('Could not locate an entry in the container with the id "%s".', TestInterface_A::class)
        );
        $this->container->get(TestInterface_A::class);
    }

    public function testGetFailed(): void {
        $this->expectException(ContainerExceptionInterface::class);
        // When fetching the value, it will break on the constructor injection and throw an exception.
        $this->expectExceptionMessage(sprintf(
            'Could not locate an entry in the container with the id "%s".',
            TestInterface_B::class
        ));
        $this->container->get(TestInterface_B::class);
    }

    public function testHasPrimitiveValue(): void {
        $this->container->set('A', 123);
        self::assertTrue($this->container->has('A'));
    }

    public function testHasClassName(): void {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        self::assertTrue($this->container->has(TestInterface_A::class));
    }

    public function testHasObject(): void {
        $this->container->set(TestInterface_C::class, new TestClass_C());
        self::assertTrue($this->container->has(TestInterface_C::class));
    }

    public function testHasFail(): void {
        self::assertFalse($this->container->has('ABCD'));
    }

    public function testUnset(): void {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        self::assertTrue($this->container->has(TestInterface_A::class));
        $this->container->unset(TestInterface_A::class);
        self::assertFalse($this->container->has(TestInterface_A::class));
    }

    public function testUnsetFail(): void {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Could not remove the given entity because it was not set.');
        $this->container->unset('not-exist!');
    }

    public function testRebind(): void {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        self::assertTrue($this->container->has(TestInterface_A::class));
        $this->container->unset(TestInterface_A::class);
        self::assertFalse($this->container->has(TestInterface_A::class));
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        self::assertTrue($this->container->has(TestInterface_A::class));
        $this->container->rebind(TestInterface_A::class, TestClass_D::class);
        self::assertInstanceOf(TestClass_D::class, $this->container->get(TestInterface_A::class));
    }

    public function testClear(): void {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        $this->container->set(TestInterface_B::class, TestClass_B::class);

        self::assertTrue($this->container->has(TestInterface_A::class));
        self::assertTrue($this->container->has(TestInterface_B::class));

        $this->container->clear();

        self::assertFalse($this->container->has(TestInterface_A::class));
        self::assertFalse($this->container->has(TestInterface_B::class));
    }

    public function testGetNoConstructorBound(): void {
        $this->container->set(TestClass_D::class, TestClass_D::class);
        self::assertInstanceOf(TestClass_D::class, $this->container->get(TestClass_D::class));
    }

    public function testGetNoBinding(): void {
        $this->container->set(TestClass_E::class, TestClass_E::class);
        $result = $this->container->get(TestClass_E::class);
        self::assertInstanceOf(TestClass_E::class, $result);
        self::assertInstanceOf(TestClass_D::class, $result->obj);
    }

    public function testGetNoTypehint(): void {
        $this->container->set(TestClass_F::class, TestClass_F::class);
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Failed to resolve type for parameter someObject (type null).');
        $this->container->get(TestClass_F::class);
    }

    public function testConstructorParamObjBind(): void {
        TestClass_C::$counter = 0;
        $this->container->set(TestInterface_C::class, new TestClass_C());
        $this->container->set(TestInterface_B::class, TestClass_B::class);
        $out = $this->container->get(TestInterface_B::class);

        self::assertInstanceOf(TestInterface_B::class, $out);
        self::assertInstanceOf(TestInterface_C::class, $out->obj);
        self::assertEquals(1, $out->obj->id);

        $out2 = $this->container->get(TestInterface_B::class);
        self::assertEquals(1, $out2->obj->id);
        self::assertNotSame($out, $out2);
    }

    public function testOffsetHas(): void {
        $this->container->set(TestClass_E::class, TestClass_E::class);
        self::assertFalse(isset($this->container['null']));
        self::assertTrue(isset($this->container[TestClass_E::class]));
    }

    public function testOffsetUnset(): void {
        $this->container->set(TestInterface_A::class, TestClass_A::class);
        self::assertTrue($this->container->has(TestInterface_A::class));
        unset($this->container[TestInterface_A::class]);
        self::assertFalse($this->container->has(TestInterface_A::class));
    }

    public function testOffsetUnsetFail(): void {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Could not remove the given entity because it was not set.');
        unset($this->container['not-exist!']);
    }

    public function testOffsetGet(): void {
        $this->container->set('Test', 'abc123');
        self::assertSame('abc123', $this->container['Test']);
    }

}
