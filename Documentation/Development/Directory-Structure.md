## Directory structure

`Project ROOT`<br />
`├── bin`<br />
`│   └── jtracker -> ../cli/tracker.php` A symbolic link to the JTracker CLI Application.<br />
`├── build`<br />
`│   ├── phpcs`<br />
`│   │   └── Joomla` The Joomla! Coding Standards<br />If this folder does not exist, run `git submodule init` and `git submodule update` from within you project root directory.<br />
`│   ├── puppet` Configuration files for the Vagrant virtual machine.<br />
`├── cache` The cache directory.<br />
`├── cli` The command line application.<br />
`│   ├── Custom_jtracker.xml` A PHPStorm autocomplete file for the JTracker CLI Application.<br />
`├── Documentation` The project documentation.<br />
`├── etc`<br />
`│   ├── config.dist.json` Copy this file to `config.json`.<br />
`│   ├── config.json` This is your configuration file.<br />
`│   ├── config.travis.json` Configuration file used for the Travis CI server.<br />
`│   ├── config.vagrant.json` Configuration file used for the Vagrant virtual machine. Note that you might issue `git update-index --assume-unchanged` for this file to prevent accidently commits.<br />
`│   └── mysql.sql` The MySQL installation file.<br />
`├── logs` The log directory (This folder must be set to 077 if you use the Vagrant box).<br />
`├── src`<br />
`│   ├── App` The custom Apps (extensions) the make up the Application.<br />
`│   ├── Joomla` Custom Joomla! Framework overrides.<br />
`│   └── JTracker` The JTracker "Core" Application.<br />
`├── templates` Template files (Twig, PHP, etc).<br />
`│   ├── JTracker`<br />
`│   │   └── g11n` The language files for the JTracker template.<br />
`│   ├── php` Currently PHP templates live here.<br />
`├── tests` The tests directory.<br />
`├── vendor` All 3rd party vendor code (PHP).<br />
`├── www` This is the Web root folder.<br />
`│   ├── fonts` Template fonts.<br />
`│   ├── images`<br />
`│   │   ├── avatars` Avatar images (This folder must be set to 077 if you use the Vagrant box).<br />
`│   │   ├── flags` Flag images for the language chooser.<br />
`│   │   ├── tracker` JTracker images.<br />
`│   ├── jtracker` Custom assets for the JTracker Application.<br />
`│   ├── vendor` All 3rd party vendor assets.<br />
`│   ├── index.php` The main entry point.<br />
`├── bower.json` Bower config file.<br />
`├── build.xml` Ant config (not used).<br />
`├── composer.json` Composer config file.<br />
`├── composer.lock` Composer lock file.<br />
`├── CONTRIBUTING.md` Contributors readme.<br />
`├── credits.json` Credits file.<br />
`├── phpunit.travis.xml` PHPUnit Travis config.<br />
`├── phpunit.xml.dist` PHPUnit config.<br />
`├── README.md` Please read me...<br />
`├── Vagrantfile` Vagrant config file.<br />
