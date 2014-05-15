## Directory structure

`Project ROOT`<br />
`├── bin`<br />
`│   └── jtracker -> ../cli/tracker.php` A symbolic link to the JTracker CLI Application.<br />
`├── build`<br />
`│   ├── phpcs`<br />
`│   │   └── Joomla` The Joomla! Coding Standards<br />If this folder does not exist, run `git submodule init` and `git submodule update` from within you project root directory.

`│   ├── puppet` Configuration files for the Vagrant virtual machine.

`├── cache` The cache directory.

`├── cli` The command line application.

`│   ├── Custom_jtracker.xml` A PHPStorm autocomplete file for the JTracker CLI Application.

`├── Documentation` The project documentation.

`├── etc`

`│   ├── config.dist.json` Copy this file to `config.json`.

`│   ├── config.json` This is your configuration file.

`│   ├── config.travis.json` Configuration file used for the Travis CI server.

`│   ├── config.vagrant.json` Configuration file used for the Vagrant virtual machine. Note that you might issue `git update-index --assume-unchanged` for this file to prevent accidently commits.

`│   └── mysql.sql` The MySQL installation file.

`├── logs` The log directory (This folder must be set to 077 if you use the Vagrant box).

`├── src`

`│   ├── App` The custom Apps (extensions) the make up the Application.

`│   ├── Joomla` Custom Joomla! Framework overrides.

`│   └── JTracker` The JTracker "Core" Application.

`├── templates` Template files (Twig, PHP, etc).

`│   ├── JTracker`

`│   │   └── g11n` The language files for the JTracker template.

`│   ├── php` Currently PHP templates live here.

`├── tests` The tests directory.

`├── vendor` All 3rd party vendor code (PHP).

`├── www` This is the Web root folder.

`│   ├── fonts` Template fonts.

`│   ├── images`

`│   │   ├── avatars` Avatar images (This folder must be set to 077 if you use the Vagrant box).

`│   │   ├── flags` Flag images for the language chooser.

`│   │   ├── tracker` JTracker images.

`│   ├── jtracker` Custom assets for the JTracker Application.

`│   ├── vendor` All 3rd party vendor assets.

`│   ├── index.php` The main entry point.

`├── bower.json` Bower config file.

`├── build.xml` Ant config (not used).

`├── composer.json` Composer config file.

`├── composer.lock` Composer lock file.

`├── CONTRIBUTING.md` Contributors readme.

`├── credits.json` Credits file.

`├── phpunit.travis.xml` PHPUnit Travis config.

`├── phpunit.xml.dist` PHPUnit config.

`├── README.md` Please read me...

`├── Vagrantfile` Vagrant config file.
