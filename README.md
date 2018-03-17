# Container

[![Build Status](https://travis-ci.org/jitesoft/php-container.svg?branch=master)](https://travis-ci.org/jitesoft/php-container)

[![codecov](https://codecov.io/gh/jitesoft/php-container/branch/master/graph/badge.svg)](https://codecov.io/gh/jitesoft/php-container)

Simple and naïve implementation of a dependency container with constructor injection.  

## Usage

### Bindings

The container binds a key to a value where the value can be any type or a class name.  

If it is a object, primitive or any type of instance, it will store the instance as a static object, 
that is: the same object will be returned at each `get` call. While if a class name is passed the container
will try to create an instance of the class on each `get` call. If it can not create an instance, a NotFoundException 
or ContainerException will be thrown.

The binding can be done at creation by passing an associative array into the constructor, or by using the `set` 
method. To re-bind, the key have to be un-set first by using the `unset` or `offsetUnset` (`unset($c['id'])`) method.  

The container implements the [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md) Container interface.
Further, the container implements the `ArrayAccess` interface, enabling fetching by using the array index syntax as: `$container[Interface::class]`.  


### Example code

```php
// Set mapping through constructor if wanted.
$container = new Container([
  'MyString' => 'This string is now mapped to the MyString',
  SpecificInterface::class => SpecificImplementation::class
]);

// It is also possible to create or fetch a container through the following static methods:
Container::createContainer('identifier1');
Container::getContainer('identifier2');

// When containers are fetched through the static methods and one should be removed, use the static removeContainer method:
Container::removeContainer('identifier1');

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


## Changes

### 3.0.0

Moved all static methods to a ContainerFactory. The container class now only contain the container specific get/set etc - methods.  
Moved injection logic into its own class which is used through the container.  
Removed exceptions from source and included a exception library instead.  

### 2.0.0

The inner containers are no longer static, instead there is static `createContainer`, `getContainer` and `removeContainer` 
methods which uses a inner static container to store the user defined containers.  
Each container has its own bindings now.  

The container now implements the `ArrayAccess` interface, which allows for using the standard array syntax (hence using the 
container as a associative array if wanted).

Improved tests which now covers 100% of the code base.

