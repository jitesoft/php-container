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
    public $obj;

    public function __construct(TestInterface_B $obj) {
        $this->obj = $obj;
    }

}

class TestClass_B implements TestInterface_B {
    public $obj;

    public function __construct(TestInterface_C $obj) {
        $this->obj = $obj;
    }

}

class TestClass_C implements TestInterface_C {
    public static $counter = 0;

    public $id;

    public function __construct() {
        self::$counter++;
        $this->id = self::$counter;
    }

}
