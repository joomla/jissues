# tracker.php

This script will do:

* Setup the required database for the application.
* Initial import and further update of issues and issue comments from GitHub.

It is meant to be used during the development process and must be run from the PHP command line interface.

Usage:

`tracker.php <command> [action]`

Available commands:

* `install` Install the application.
* `get` Retrieve new issues, comments, avatars, ...
* `make` Make documentation, ...
* `help` Display some helpful text.

For more information use `tracker.php help <command>`.

## Install the application

Copy `/etc/config.example.json` to `/etc/config.json` and fill in your database details.

Then run:
`tracker.php install`

## Retrieve new Issues and Comments

`tracker.php retrieve issues`

`tracker.php retrieve comments`

## Colors
Recently a new feature has been added to the framework that allows CLI applications to display colorful output on ANSI enabled terminals. So I thought we might see how it looks and feels ;)

ANSI color codes are supported in most (if not all) *nix style terminals.
To test this feature I grabbed a VM with Windows XP, installed git for Windows and GitHub (both include terminals) but neither them nor the standard Windows thingy supported ANSI colors.
Then I installed [Cygwin](http://www.cygwin.com/) (which is a good choice anyway), and got the following output:
![win-colors1](https://f.cloud.github.com/assets/33978/491726/2c5ff9b4-ba54-11e2-80eb-76a29914d58a.png)

## Progress Bar
Since we have some long runng operations (currently ~10 min pulling the CMS issues on my slow i-net), I thought we might use some "high class" progress bar.

The progress bar is not part of the repo and has to be installed using composer from [elkuku/console-progressbar](https://packagist.org/packages/elkuku/console-progressbar) (which is a fork of [PEAR/Console_ProgressBar](http://pear.php.net/package/Console_ProgressBar) with a facelifting ;)
I haven't tried that on windows, but it might work on cygwin...
![progressbar3](https://f.cloud.github.com/assets/33978/491733/a36ce152-ba54-11e2-8c06-179b6a379876.png)

## Unsupported...
If your terminal does not support ANSI control codes you may see something like this:
![win-colors-fail](https://f.cloud.github.com/assets/33978/491728/57cc233e-ba54-11e2-9c6b-154ad99488fd.png)

## Turn it off !
To suppress color ouput for a single command use the `--nocolors` switch.
To suppress the progress bar for a single command use the `--noprogress` switch.
Example:
`tracker.php retrieve issues --nocolors --noprogress`

To turn the feature(s) off permanently edit `etc/config.json` and set the values for the undesired features from `1` to `0`.

----
Since most of the code in this PR has been used to actually test this feature before submitting it to the framework, I thought I could give this here back too.

If you have any strong feelings against this, please raise your voice here and now or I am going to merge it ;)
