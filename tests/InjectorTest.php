<?php /** @noinspection PhpUnhandledExceptionInspection */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  InjectorTest.php - Part of the container project.

  © - Jitesoft
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

use Jitesoft\Container\Container;
use Jitesoft\Container\Injector;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InjectorTest extends TestCase {
    protected Injector $injector;
    protected Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
        $this->injector  = new Injector($this->container);
    }

    public function testCreateNoCtorWithContainer(): void {
        $this->container[TestClass_D::class] = TestClass_D::class;
        self::assertInstanceOf(TestClass_D::class, $this->injector->create(TestClass_D::class));
    }

    public function testCreateNoCtorWithBinding(): void {
        self::assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_D::class, [TestClass_D::class => TestClass_D::class])
        );
    }

    public function testCreateNoBindWithContainer(): void {
        $this->container[TestClass_E::class] = TestClass_E::class;
        self::assertInstanceOf(
            TestClass_E::class,
            $this->injector->create(TestClass_E::class)
        );

        self::assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_E::class)->obj
        );

        self::assertNotSame(
            $this->injector->create(TestClass_E::class),
            $this->injector->create(TestClass_E::class)
        );
    }

    public function testCreateNoBindWithBindings(): void {
        self::assertInstanceOf(
            TestClass_E::class,
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])
        );

        self::assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])->obj
        );

        self::assertNotSame(
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class]),
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])
        );
    }

    public function testCreateNoTypeHintFoundWithContainer(): void {
        $this->container[TestClass_F::class] = TestClass_F::class;
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Failed to resolve type for parameter someObject (type null).');
        $this->injector->create(TestClass_F::class);
    }

    public function testCreateNoTypeHintFoundWithBindings(): void {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Failed to resolve type for parameter someObject (type null).');
        $this->injector->create(TestClass_F::class, [TestClass_F::class, TestClass_F::class]);
    }

    public function testCreateWithCtorPramObjectBindWithContainer(): void {
        TestClass_C::$counter = 0;

        $this->container[TestInterface_C::class] = new TestClass_C();
        $this->container[TestInterface_B::class] = TestClass_B::class;

        $out = $this->injector->create(TestClass_B::class);
        self::assertInstanceOf(TestInterface_B::class, $out);
        self::assertInstanceOf(TestInterface_C::class, $out->obj);
        self::assertEquals(1, $out->obj->id);

        $out2 = $this->injector->create(TestClass_B::class);
        self::assertEquals(1, $out2->obj->id);
        self::assertNotSame($out, $out2);
    }

    public function testCreateWithCtorPramObjectBindWithBindings(): void {
        TestClass_C::$counter = 0;

        $c   = new TestClass_C();
        $out = $this->injector->create(
            TestClass_B::class,
            [TestInterface_C::class => $c, TestInterface_B::class => TestClass_B::class]
        );
        self::assertInstanceOf(TestInterface_B::class, $out);
        self::assertInstanceOf(TestInterface_C::class, $out->obj);
        self::assertEquals(1, $out->obj->id);

        $out2 = $this->injector->create(
            TestClass_B::class,
            [TestInterface_C::class => $c, TestInterface_B::class => TestClass_B::class]
        );
        self::assertEquals(1, $out2->obj->id);
        self::assertNotSame($out, $out2);
    }

    public function testCreateInvalidType(): void {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Failed to create reflection class from given class name.');
        $this->injector->create('InvalidClassName123123');
    }

    public function testCreateFunctionNoParams(): void {
        $cb = static fn() => 'abc';

        $result = $this->injector->invoke($cb);
        self::assertEquals('abc', $result);
    }

    public function testCreateFunctionParams(): void {
        $this->container[TestInterface_B::class] = TestClass_B::class;
        $this->container[TestInterface_C::class] = TestClass_C::class;
        $cb = static fn(TestInterface_B $b) => new TestClass_A($b);
        $result = $this->injector->invoke($cb, []);

        self::assertInstanceOf(TestClass_B::class, $result->obj);
        self::assertInstanceOf(TestClass_A::class, $result);
    }

}
