## Directory structure

`Project ROOT`<br />
`├── .github` GitHub repository metadata<br />
`├── bin`<br />
`│   └── jtracker -> ../cli/tracker.php` A symbolic link to the JTracker CLI Application.<br />
`├── build`<br />
`│   ├── phpcs`<br />
`│   │   └── Joomla` The Joomla! Coding Standards<br />If this folder does not exist, run `git submodule init` and `git submodule update` from within your project root directory.<br />
`│   ├── puppet` Configuration files for the Vagrant virtual machine.<br />
`├── cache` The cache directory.<br />
`├── cli` The command line application.<br />
`│   ├── Application` All code for the command line application.<br />
`│   ├── completions` Autocomplete files for various command line resources.<br />
`│   ├── g11n` Translations for the command line application's output.<br />
`│   ├── tracker.php` The front controller for the command line application.<br />
`├── Documentation` The project documentation.<br />
`├── etc`<br />
`│   ├── migrations` Database migrations
`│   ├── config.dist.json` Copy this file to `config.json`.<br />
`│   ├── config.json` This is your configuration file.<br />
`│   ├── config.travis.json` Configuration file used for the Travis CI server.<br />
`│   ├── config.vagrant.json` Configuration file used for the Vagrant virtual machine. Note that you might issue `git update-index --assume-unchanged` for this file to prevent accidently commits.<br />
`│   └── mysql.sql` The MySQL installation file.<br />
`├── logs` The log directory (This folder must be set to 077 if you use the Vagrant box).<br />
`├── src`<br />
`│   ├── App` The custom Apps (extensions) the make up the Application.<br />
`│   └── JTracker` The JTracker "Core" Application.<br />
`├── templates` Template files (Twig, PHP, etc).<br />
`│   ├── JTracker`<br />
`│   │   └── g11n` The language files for the JTracker template.<br />
`├── tests` The tests directory.<br />
`├── vendor` All 3rd party vendor code (PHP).<br />
`├── www` This is the Web root folder.<br />
`│   ├── images`<br />
`│   │   ├── avatars` Avatar images (This folder must be set to 077 if you use the Vagrant box).<br />
`│   ├── media` Most web accessible media (CSS, JavaScript, fonts) lives here. Also, if you have set up Bower and Grunt for development, uncompressed media from Bower projects is placed here.<br />
`│   ├── index.php` The front controller for the web application.<br />
`├── .gitignore` Git ignore definitions.<br />
`├── .gitmodules` Git submodule definitions.<br />
`├── .php_cs` PHP-CS-Fixer config file.<br />
`├── .travis.yml` Travis-CI config file.<br />
`├── bower.json` Bower config file.<br />
`├── build.xml` Ant config (not used).<br />
`├── composer.json` Composer config file.<br />
`├── composer.lock` Composer lock file.<br />
`├── credits.json` Credits file.<br />
`├── Gruntfile.js` Grunt task configuration.<br />
`├── package.json` Node package definition.<br />
`├── phpunit.xml` PHPUnit config.<br />
`├── README.md` Please read me...<br />
`├── Vagrantfile` Vagrant config file.<br />
