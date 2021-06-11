[![pipeline status](https://gitlab.com/jitesoft/open-source/php/container/badges/master/pipeline.svg)](https://gitlab.com/jitesoft/open-source/php/container/-/commits/master)
[![coverage report](https://gitlab.com/jitesoft/open-source/php/container/badges/master/coverage.svg)](https://gitlab.com/jitesoft/open-source/php/container/commits/master)
[![Back project](https://img.shields.io/badge/Open%20Collective-Tip%20the%20devs!-blue.svg)](https://opencollective.com/jitesoft-open-source)
[![Maintainability](https://api.codeclimate.com/v1/badges/55642eb87b3868861d91/maintainability)](https://codeclimate.com/github/jitesoft/php-container/maintainability)

# Container

[IoC](https://en.wikipedia.org/wiki/Inversion_of_control) container with [constructor dependency injection](https://en.wikipedia.org/wiki/Dependency_injection).

## Bindings

The container binds a key to a value where the value can be any type or a class name.  

If it is a object, primitive or any type of instance, it will store the instance as a static object, 
that is: the same object will be returned at each `get` call. While if a class name is passed the container
will try to create an instance of the class on each `get` call. If it can not create an instance, a NotFoundException 
or ContainerException will be thrown.

The binding can be done at creation by passing an associative array into the constructor, or by using the `set` 
method. To re-bind, the `rebind` method - accepting a key and a value - can be used.  

The container implements the [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md) Container interface.
Further, the container implements the `ArrayAccess` interface, enabling fetching by using the array index syntax as: `$container[Interface::class]`.  

## Usage

The container implements the following interfaces:


```php
interface ContainerInterface {
  public function get($abstract): mixed;
  public function has($abstract): bool;
}

interface ArrayAccess {
  public function offsetExists($offset);
  public function offsetGet($offset);
  public function offsetSet($offset, $value);
  public function offsetUnset($offset);
}
```

The `ArrayAccess` interface allows for getting, setting and un-setting bindings through the array index syntax:

```php
$c['Abstract'] = 'Concrete';
echo $c['Abstract']; // 'Concrete'
unset($c['Abstract'];
```

Furthermore, the class implements the following public methods:

```php
class Container {
  public function __construct(?array $bindings);
  public function clear();
  public function set(string $abstract, mixed $concrete, ?bool $singleton = false): bool;
  public function rebind(string $abstract, mixed $concrete, ?bool $singleton = false): void;
  public function unset(string $abstract): void;
  
  public function singleton(string $abstract, mixed $concrete): void;
}
```

The constructor of the class takes an optional bindings array. The array expected to be an associative array
containing the abstract as key and concrete as value. If wanted, the concrete could be another associative array with
a `class` or `func` key containing the class or callable to resolve to/with and a `singleton` key with a boolean value.  
If the singleton key is true, the container will only ever create a single instance of the resolved value or only run 
the resolve function once.

Example:

```php
$container = new Container([

  // With objects:
  InterfaceA::class => $objectInheritingInterfaceA,
  InterfaceB::class => [
    'class' => $objectInheritingInterfaceB,
    'singleton' => true
  ],

  // With classes:
  InterfaceA::class => ClassA::class,
  InterfaceB::class => [
    'class' => ClassB::class,
    'singleton' => true
  ],

  // Or with functions:
  InterfaceA::class => static fn (InterfaceB $ib) => new ClassA($ib),
  InterfaceB::class => [
    'class' => static function(InterfaceC $c) {
      return new ClassB($c);
    },
    'singleton' => true
  ],

]);

$container->get(InterfaceA::class); // Will be a new object of the ClassA class.
$container->get(InterfaceB::class); // On first call, it will be resolved to a ClassB class.
$container->get(InterfaceB::class); // On all other calls, the object will be the same as the first call.
```

Alternatively to the array type singleton binding, the interface will create a singleton binding with the `singleton` method.

Rebinding can be done in runtime with the `$container->rebind($a, $c, $singleton);` method.  
This will unset the earlier binding and create a new.  

To remove all the current bindings, the `$container->clear();` method can be used, which will empty the inner
list of entries. Observe that this will not clear up the currently resolved instances of objects stored in your 
classes, but rather just remove all the entries from the container.

All the methods implemented in the class (with an exception in the `has` and `clear` methods) will throw exceptions on errors.  
The following two exceptions are used:

```
Jitesoft\Exceptions\Psr\Container\ContainerException implements ContainerExceptionInterface;
Jitesoft\Exceptions\Psr\Container\NotFoundException  implements NotFoundExceptionInterface;
```

So when checking for exceptions, one could use either the underlying `JitesoftException` class, the specific classes or 
the interfaces.  
Observe though that the `NotFoundException` inherits from the `ContainerException` so in the cases where both can be returned and 
you want to catch the specific exceptions, catch the `NotFoundException` before the `ContainerException`.

### Dependency injection

The container will, in the cases where it is able to, inject dependencies into the constructor when resolving the object.  
There are some requirements before it will be able to do this though:  
  
1. The parameter need to be typehinted.
2. The parameter need to be possible to resolve in the container or be possible to creat without constructor.

If the container can not resolve the parameter, it will throw an exception, but following the above two requirements, this should not happen.

```php
class ClassA {}

class ClassB {
  __constructor(ClassA $b) { }
}

$container->set(ClassB::class, ClassB::class);
$container->get(ClassB::class); // Will throw a ContainerException due to class A not being bound.

$container->set(ClassA::class, ClassA::class);
$container->get(ClassB::class); // Will not throw any exception. ClassA is resolved and pushed into the constructor of ClassB.
```

### Not only classes

The container does not only resolve bindings, but can be used to store other values too.  
If the passed concrete is not a class name, it will use it as a single value and not resolve. So passing a string or number
as the concrete will make the `get` method return the value.  

```php
$container->set('Something', 123);
$container->get('Something'); // 123
```

## License

MIT
