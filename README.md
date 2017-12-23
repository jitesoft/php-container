# Container

[![Build Status](https://travis-ci.org/jitesoft/php-container.svg?branch=master)](https://travis-ci.org/jitesoft/php-container)

Simple and naïve implementation of a dependency container with constructor injection.  

## Usage

### Bindings

The container binds a key to a value where the value can be any type or a class name.  

If it is a type, object, primitive or the likes, it will store the instance as bound, the same object will be 
returned at each `get` call.  
The binding can be done at creation by passing an associative array into the constructor, or by using the `set` 
method. To re-bind, the key have to be un-set first by using the `unset` method.  
Fetching the value is easily done with the `get` method.
If a class name is passed, the container will try to create an instance from the class and will try to inject any 
constructor parameters which are type-hinted and bound. If it can not, an exception will be thrown.

The container implements the [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md) Container interface.

### Example code

```php
// Set mapping through constructor if wanted.
$container = new Container([
  'MyString' => 'This string is now mapped to the MyString',
  SpecificInterface::class => SpecificImplementation::class
]);

// Or with the set method.
$container->set(AnotherInterface::class, AnotherClass::class);

// The following will throw an exception due to the fact that the mapping already exist (looks for key).
$container->set(AnotherInterface::class, AnotherClass::class);

// Unset the mapping to remove it. Then it's okay to use the same interface as key again.
$container->unset(AnotherInterface::class);

// Fetch a mapped value from the container with get. In this case the instance will be of the AnotherClass type.
$specificInstance = $container->get(SpecificInterface::class);

// Constructor injection is possible, but it have to have typehinted and mapped types to be able to set it.
$container->set(SomeInterface::class, ClassWhichTakesASpecificInterfaceInConstructor::class);

// Will fetch the class bound above and inject the binding of SpecificInterface into the constructor.
$container->get(SomeInterface::class);

// Mapping of objects is possible to, the objects will be bound as singletons and will not be recreated.
$container->set(SomeOtherInterface::class, new SomeClass());
```

## License

MIT
