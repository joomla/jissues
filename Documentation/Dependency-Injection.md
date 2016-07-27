## Dependency Injection

The issue tracker application makes use of Dependency Injection (DI) and Service Providers.

The application uses the Joomla! Framework's [DI Container](https://github.com/joomla-framework/di).  At present, this provides a hybrid solution which emulates the old `JFactory` functions but helps with a full transition to DI based loading.

The Container instance is instantiated within the application and several service providers are added for global use in the application.

To retrieve an object from the container, you must call `$container->get($key, $forceNew)` where $key is the key for the object (app, config, db) and $forceNew instructs the container to create a new instance of the specified object.

The service provider should contain the logic needed to build the object and provide default instructions to the Container on how it is accessed.  Our service providers are set up so that the objects created cannot be overridden by a different object of the same name and will create a shared (reusable) instance of the object.

### App Services

Each app within the application must have a base class that implements the `JTracker\AppInterface` (this could be compared to a Symfony bundle as a high level example).  The interface defines a `loadServices` method which receives the global DI container as its single parameter.  Apps should use this to add services created within the app to the container or to register additional options to global services.
