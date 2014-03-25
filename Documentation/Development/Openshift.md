## Openshift PaaS

### WhatIs https://www.openshift.com/

The Open Hybrid Cloud Application Platform by Red Hat

`@todo`

### Setup

* Go to Openshift, create a new application type `PHP 5.4`.
* Under "Source code" put `https://github.com/joomla/jissues.git` and the branch `openshift` (@todo` master?)
* => Create Application ....
* Look around, then click on "Continue to the application overview page".
* Click "Add MySQL 5.5" => "Add Cartridge"

Here comes a little quirk.... When you call the page now, you get a "Could not connect to MySQL" error.

* Clone the openshift repo
* Make a "dummy" commit and push it. - This should solve the issue.

Cause: Some Openshift env vars get lost (like MySQL stuff) `@todo` remove when solved.

Continue with installation
* SSH into the application
* `cd  app-root/repo/`
* `export JTRACKER_ENVIRONMENT=openshift && ./bin/jtracker install` (`@todo` env var has to be passed "by hand"..)

Done.
