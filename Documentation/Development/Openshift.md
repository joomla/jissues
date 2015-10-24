## Openshift PaaS

### WhatIs https://www.openshift.com/

Short: The Open Hybrid Cloud Application Platform by Red Hat

`@todo` more info

### Setup

* Go to https://openshift.redhat.com/app/console/application_types?search=php, create a new application type `PHP, MySQL, and phpMyAdmin`.
* Under "Source code" put `https://github.com/joomla/jissues.git` and the branch `openshift` (`@todo` master?)
* => Create the Application ....
* Look around, then click on "Continue to the application overview page".

### Environment Variables

Several parameters have to be passed as environment variables to make the application aware of the Openshift environment. This is also a security feature.

Use the rhc client tools to set the environment variables:

`rhc env set <var_name>=<var_value> -a <application>`

```
JTRACKER_ENVIRONMENT="openshift"
JTRACKER_GITHUB_CLIENT_ID=<github_client_id>
JTRACKER_GITHUB_CLIENT_SECRET=<github_client_secret>
JTRACKER_GITHUB_USERNAME=<your_github_username>
JTRACKER_GITHUB_PASSWORD=<your_github_password>
```

e.g.:

`rhc env set JTRACKER_ENVIRONMENT=openshift -a trackertest`

### Installation

* Open your terminal and SSH into the application.
* `cd  app-root/repo/bin`
* `./jtracker install`

Done.
