## CLI - Command Line Application

The script is located at `/bin/jtracker`, which is a symlink to `/cli/tracker.php` created for convenience.
It is meant to be executed on the computers [command line interface](https://en.wikipedia.org/wiki/Command-line_interface).

This script will do:

* Setup the required database for the application.
* Initial import and further update of issues and issue comments from GitHub.
* Lots of other functionality for maintaining the application in sync and generate lots of useful stuff.

Usage:

`jtracker <command> [action]`

See: `jtracker help` for more information.
Or: `jtracker help <command>` for more information on a specific command.

The output of the script is language aware. Just add a `--lang` argument:

`jtracker get project --lang=ru-RU`

### Install the application

Copy `/etc/config.dist.json` to `/etc/config.json` and fill in your database details. To interface with GitHub, fill in your GitHub credentials.

Then run:
	`jtracker install`

### Import a project from GitHub

The command `jtracker get project` will retrieve the information for a given project from GitHub.
This should be used during installation and periodical executions.

To bypass inputs and write the output to a log file during cron execution, a similar command could be used:

`jtracker get project -p 2 --all --status=all --quiet --noprogress --log cron.log`

*Note* `get project` will "batch run" the available `get` commands in the correct (..erm) order.

### Colors

Recently a new feature has been added to the framework that allows CLI applications to display colorful output on ANSI enabled terminals. So I thought we might see how it looks and feels ;)

ANSI color codes are supported in most (if not all) *nix style terminals.
To test this feature I grabbed a VM with Windows XP, installed git for Windows and GitHub (both include terminals) but neither them nor the standard Windows thingy supported ANSI colors.
Then I installed [Cygwin](http://www.cygwin.com/) (which is a good choice anyway), and got the following output:

![win-colors1](https://f.cloud.github.com/assets/33978/491726/2c5ff9b4-ba54-11e2-80eb-76a29914d58a.png)

### Progress Bar

Since we have some long running operations (currently ~10 min pulling the CMS issues on my slow i-net), I thought we might use some "high class" progress bar.

The progress bar is not part of the repo and has to be installed using composer from [elkuku/console-progressbar](https://packagist.org/packages/elkuku/console-progressbar) (which is a fork of [PEAR/Console_ProgressBar](http://pear.php.net/package/Console_ProgressBar) with a facelifting ;)
I haven't tried that on Windows, but it might work on cygwin...

![progressbar3](https://f.cloud.github.com/assets/33978/491733/a36ce152-ba54-11e2-8c06-179b6a379876.png)

### Unsupported...

If your terminal does not support ANSI control codes you may see something like this:

![win-colors-fail](https://f.cloud.github.com/assets/33978/491728/57cc233e-ba54-11e2-9c6b-154ad99488fd.png)

### Turn it off !

To suppress color ouput for a single command use the `--nocolors` switch.
To suppress the progress bar for a single command use the `--noprogress` switch.
Example:
`jtracker get project --nocolors --noprogress`

To turn the feature(s) off permanently edit `etc/config.json` and set the values for the undesired features from `1` to `0`.

### Auto Complete

Auto complete files are provided for some environments.

Optionally you can generate the files four your language using

`jtracker make autocomplete --lang=xx-XX`

using one of the supported languages.

#### PHPStorm

If you use PHPStorm, you may use the [Command Line Tools Console](http://www.jetbrains.com/phpstorm/webhelp/command-line-tools-console-tool-window.html) to execute the jtracker script.

To get auto complete for the `jtracker` commands, copy the file `Custom_jtracker.xml` to the folder `.idea/commandlinetools` inside your JTracker project (create the folder if it does not exist). This will set up an alias `jt` that points to the `/bin/jtracker` script.

![cli-auto-complete](https://f.cloud.github.com/assets/2059654/738999/cc8f5ba2-e351-11e2-8389-8fbb1e4a3243.png)

Don't miss the documentation - Press <kbd>Ctrl</kbd> + <kbd>Q</kbd>

![cli-auto-complete1](https://f.cloud.github.com/assets/2059654/739003/d0295894-e351-11e2-8ee6-973d8741a3cd.png)

#### The `fish` shell

Provides auto complete for users of the [fish shell](https://fishshell.com).

To use it, copy (or better symlink) the file 
`{repo}/cli/completions/jtracker.fish`
to
`~/.config/fish/completions/`
and restart your shell.

Works in PHPStorms "Terminal".

![2016-07-15-125504_1366x768_scrot](https://cloud.githubusercontent.com/assets/33978/16890651/1dc653da-4ab6-11e6-91d8-ba62b1d11603.png)

![2016-07-15-125543_1366x768_scrot](https://cloud.githubusercontent.com/assets/33978/16890652/1dce0ada-4ab6-11e6-963c-01a0e5c2240a.png)

![2016-07-15-125430_1366x768_scrot](https://cloud.githubusercontent.com/assets/33978/16890650/1dc25906-4ab6-11e6-88d0-aeb7d1386e4a.png)

And of course in your favourite terminal too.

![tty](https://cloud.githubusercontent.com/assets/33978/16890656/2373b23c-4ab6-11e6-875b-d90f7d1de8a2.gif)

Happy auto completing `=;)`
