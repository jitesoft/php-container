<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  TestObjects.php - Part of the container project.

  © - Jitesoft 2017
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace Jitesoft\Container\Tests;

interface TestInterface_A {}
interface TestInterface_B {}
interface TestInterface_C {}


class TestClass_A implements TestInterface_A {
    public TestInterface_B $obj;

    public function __construct(TestInterface_B $obj) {
        $this->obj = $obj;
    }

}

class TestClass_B implements TestInterface_B {
    public TestInterface_C $obj;

    public function __construct(TestInterface_C $obj) {
        $this->obj = $obj;
    }

}

class TestClass_C implements TestInterface_C {
    public static int $counter = 0;

    public int $id;

    public function __construct() {
        self::$counter++;
        $this->id = self::$counter;
    }

}

class TestClass_D {

}

class TestClass_E {

    public TestClass_D $obj;

    public function __construct(TestClass_D $obj) {
        $this->obj = $obj;
    }
}

class TestClass_F {

    public function __construct($someObject) {
    }

}
