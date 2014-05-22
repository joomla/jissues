## Testing

Testing is still... WIP

You can execute the whole "test suite" using the command:

* `./bin/jtracker test run` from the root of your repository.

### Code Style

* We use [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to ensure a consistent coding style.
* We use the [Joomla! coding standards](https://github.com/joomla/coding-standards) which are included in the repo as a git submodule in `/build/phpcs/Joomla`.
    * If this folder is missing, issue the commands `git submodule init` and `git submodule update` from the root of your repository.

You can run the checkstyle tests using the command:

* `./bin/jtracker test checkstyle` from the root of your repository.

### PHPUnit

* We use [PHPUnit](http://phpunit.de/) to run unit tests.

You can run the unit tests using the command:

* `./bin/jtracker test phpunit` from the root of your repository.

### Behat

* Behat - http://behat.org/
* Mink - http://mink.behat.org/

#### Run the Behat tests

* The web path is set to `http://jtracker.local` in `behat.yml` file. Multiple options here (`@todo` pick one):
    * Each developer adjust their `hosts` file.
    * Each developer changes their `behat.yml` file.
    * We use the Vagrant adress `http://loclhost:2345` in the `behat.yml` file.
* Run `./vendor/bin/behat` from the repo root.

You can run the Behat tests using the command:

* `./vendor/bin/behat` from the root of your repository. (`@todo` integrate with JTracker CLI)
