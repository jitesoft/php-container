<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  InjectorTest.php - Part of the container project.

  © - Jitesoft 2018
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

use Jitesoft\Container\Container;
use Jitesoft\Container\Injector;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InjectorTest extends TestCase {

    /** @var Injector */
    protected $injector;
    /** @var Container */
    protected $container;

    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
        $this->injector  = new Injector($this->container);
    }

    public function testCreateNoCtorWithContainer() {
        $this->container[TestClass_D::class] = TestClass_D::class;
        $this->assertInstanceOf(TestClass_D::class, $this->injector->create(TestClass_D::class));
    }

    public function testCreateNoCtorWithBinding() {
        $this->assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_D::class, [TestClass_D::class => TestClass_D::class])
        );
    }

    public function testCreateNoBindWithContainer() {
        $this->container[TestClass_E::class] = TestClass_E::class;
        $this->assertInstanceOf(
            TestClass_E::class,
            $this->injector->create(TestClass_E::class)
        );

        $this->assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_E::class)->obj
        );

        $this->assertNotSame(
            $this->injector->create(TestClass_E::class),
            $this->injector->create(TestClass_E::class)
        );
    }

    public function testCreateNoBindWithBindings() {
        $this->assertInstanceOf(
            TestClass_E::class,
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])
        );

        $this->assertInstanceOf(
            TestClass_D::class,
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])->obj
        );

        $this->assertNotSame(
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class]),
            $this->injector->create(TestClass_E::class, [TestClass_E::class => TestClass_E::class])
        );
    }

    public function testCreateNoTypeHintFoundWithContainer() {
        $this->container[TestClass_F::class] = TestClass_F::class;
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Constructor parameter "someObject" could not be created.');
        $this->injector->create(TestClass_F::class);
    }

    public function testCreateNoTypeHintFoundWithBindings() {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Constructor parameter "someObject" could not be created.');
        $this->injector->create(TestClass_F::class, [TestClass_F::class, TestClass_F::class]);
    }

    public function testCreateWithCtorPramObjectBindWithContainer() {
        TestClass_C::$counter = 0;

        $this->container[TestInterface_C::class] = new TestClass_C();
        $this->container[TestInterface_B::class] = TestClass_B::class;

        $out = $this->injector->create(TestClass_B::class);
        $this->assertInstanceOf(TestInterface_B::class, $out);
        $this->assertInstanceOf(TestInterface_C::class, $out->obj);
        $this->assertEquals(1, $out->obj->id);

        $out2 = $this->injector->create(TestClass_B::class);
        $this->assertEquals(1, $out2->obj->id);
        $this->assertNotSame($out, $out2);
    }

    public function testCreateWithCtorPramObjectBindWithBindings() {
        TestClass_C::$counter = 0;

        $c   = new TestClass_C();
        $out = $this->injector->create(
            TestClass_B::class,
            [TestInterface_C::class => $c, TestInterface_B::class => TestClass_B::class]
        );
        $this->assertInstanceOf(TestInterface_B::class, $out);
        $this->assertInstanceOf(TestInterface_C::class, $out->obj);
        $this->assertEquals(1, $out->obj->id);

        $out2 = $this->injector->create(
            TestClass_B::class,
            [TestInterface_C::class => $c, TestInterface_B::class => TestClass_B::class]
        );
        $this->assertEquals(1, $out2->obj->id);
        $this->assertNotSame($out, $out2);
    }

    public function testCreateInvalidType() {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Failed to create reflection class from given class name.');
        $this->injector->create('InvalidClassName123123');
    }

}
