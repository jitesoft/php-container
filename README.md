# Container

Simple and naïve implementation of a dependency container with constructor injection.  

## Usage

Bind a ID to a value, class or primitive with the `$container->set($id, $value)` method.  
Fetch from the container with `$container->get($id)`.

The container uses static arrays for its implementations and container data. So there can only be one container active at a time.  

Better documentation will be available at a later point.

## License

MIT
