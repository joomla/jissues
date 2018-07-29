## Directory structure

`Project ROOT`<br />
`├── .github` GitHub repository metadata<br />
`├── assets` Issue Tracker frontend assets<br />
`│   ├── js` JavaScript assets, when compiled these will also be transpiled to ES5 standards.<br />
`│   ├── scss` Project style rules to be compiled into CSS.<br />
`├── bin`<br />
`│   └── jtracker -> ../cli/tracker.php` A symbolic link to the JTracker CLI Application.<br />
`├── build`<br />
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
`├── node_modules` Created after running the `npm install` command, this holds all 3rd party Node vendor code.<br />
`├── src`<br />
`│   ├── App` The custom Apps (extensions) the make up the Application.<br />
`│   └── JTracker` The JTracker "Core" Application.<br />
`├── templates` Template files (Twig, PHP, etc).<br />
`│   ├── JTracker`<br />
`│   │   └── g11n` The language files for the JTracker template.<br />
`├── tests` The tests directory.<br />
`├── vendor` Created after running the `composer install` command, this holds all 3rd party PHP vendor code.<br />
`├── www` This is the Web root folder.<br />
`│   ├── images`<br />
`│   │   ├── avatars` Avatar images (This folder must be set to 077 if you use the Vagrant box).<br />
`│   ├── media` Most web accessible media (CSS, JavaScript, fonts) lives here.<br />
`│   ├── index.php` The front controller for the web application.<br />
`├── .gitignore` Git ignore definitions.<br />
`├── .npmrc` NPM project configuration.<br />
`├── .php_cs` PHP-CS-Fixer config file.<br />
`├── .travis.yml` Travis-CI config file.<br />
`├── build.xml` Ant config (not used).<br />
`├── composer.json` Composer config file.<br />
`├── composer.lock` Composer lock file.<br />
`├── credits.json` Credits file.<br />
`├── LICENSE` Application license.<br />
`├── package.json` Node package definition.<br />
`├── package.json` Node package lock file.<br />
`├── phpunit.xml` PHPUnit config.<br />
`├── README.md` Please read me...<br />
`├── ruleset.xml` PHP_CodeSniffer config.<br />
`├── Vagrantfile` Vagrant config file.<br />
`├── webpack.mix.js` Laravel Mix (Webpack) configuration file.<br />
