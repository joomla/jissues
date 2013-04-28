# tracker.php

This script will do:

* Setup the required database for the application.
* Initial import and further update of issues and issue comments from GitHub.

It is meant to be used during the development process and must be run from the PHP command line interface.

Usage:

`tracker.php <command> [action]`

Available commands:

* `install` Install the application.
* `retrieve` Retrieve new issues and comments
* `help` Display some helpful text.

For more information use `tracker.php help <command>`.

## Install the application

Copy `/etc/configuration.example.php` to `/etc/configuration.php` and fill in your database details.

Then run:
`tracker.php install`

## Retrieve new Issues and Comments

`tracker.php retrieve issues`

`tracker.php retrieve comments`

## GitHub Authentication

GitHub limits requests to its API to 60 per hour for unauthenticated requests, and to 5000 per hour for requests using either basic authentication or oAuth.

For the initial import of issues and issue comments to the database we need to authenticate with GitHub to avoid to exceed the rate limit.

To use your GitHub credentials from the CLI script, edit the `configurqation.php` file and fill in your GitHub username and password, answer "yes" to the question if you whish to authenticate, or pass the `--auth` option.
