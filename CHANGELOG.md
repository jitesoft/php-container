### 4.0.0

Removed the ContainerFactory. If one needs multiple containers, implement your own!  
Rewrote injection logic to run through a ContainerEntry object, which is a simple structure to contain the
data that the container requires from a binding.

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

