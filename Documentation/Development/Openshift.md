## Openshift PaaS

### WhatIs https://www.openshift.com/

Short: The Open Hybrid Cloud Application Platform by Red Hat

`@todo` more info

### Setup

* Go to https://openshift.redhat.com/app/console/application_types?search=php, create a new application type `PHP, MySQL, and phpMyAdmin`.
* Under "Source code" put `https://github.com/joomla/jissues.git` and the branch `openshift` (`@todo` master?)
* => Create the Application ....
* Look around, then click on "Continue to the application overview page".

### Installation

* Open your terminal and SSH into the application.
* `cd  app-root/repo/`
* `export JTRACKER_ENVIRONMENT=openshift && ./bin/jtracker install` (`@todo` env var has to be passed "by hand"..)

Done.
