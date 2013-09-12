## Dependency Injection

The issue tracker application, in conjunction with its parent, the Joomla Framework, are transitioning to the use of Dependency Injection (DI) and Service Providers.

The application has a custom [DI Container](https://github.com/joomla/jissues/blob/framework/src/JTracker/Container.php) which extends `Joomla\DI\Container`.  At present, this provides a hybrid solution which emulates the old `JFactory` functions but helps with a full transition to DI based loading.

The Container instance is instantiated within the application and several service providers are added for global use in the application.  At present, this includes the application, configuration, and `Joomla\Database\DatabaseDriver` instance.

To retrieve an object from the container, you must call `JTracker\Container::retrieve($key, $forceNew)` where $key is the key for the object (app, config, db) and $forceNew instructs the container to create a new instance of the specified object.

The service provider should contain the logic needed to build the object (currently this isn't the case for the application) and provide default instructions to the Container on how it is accessed.  Our service providers are set up so that the objects created cannot be overridden by a different object of the same name and will create a shared (reusable) instance of the object.
