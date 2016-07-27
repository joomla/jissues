## GitHub Authentication

GitHub limits requests to its API to 60 per hour for unauthenticated requests, and to 5000 per hour for requests using either basic authentication or oAuth.

For the initial import of issues and issue comments to the database we need to authenticate with GitHub to avoid to exceed the rate limit.

To use your GitHub credentials from the CLI script, edit the `etc/config.json` file and fill in your GitHub username and password, answer "yes" to the question if you wish to authenticate, or pass the `--auth` option.

This will add the possibility to authenticate a user with their GitHub account using oAuth authentication.

#### oAuth login
In order to test the login feature in your local environment you will need to create an application `key` and `secret` for your (local) JTrackerApplication instance:

* Sail to your account on GitHub &rArr; "Edit your Profile".
* Go to "Applications" - "Developer applications" and "Register new application"
* Fill in some name and some main URL. Those will be presented to the user when authorizing the application.
* Fill in a domain for callback URL. This **must match** the domain the application is running. This may be `http://localhost` or a virtual host.
* Hit "Save" and copy the client_id and client_secret.
* Edit `etc/config.json` and fill in the `client_id` and `client_secret`.
* Install as usual.
* Sail to your localhost's JTracker installation and click on "Login with GitHub"
* On the first attempt you will be redirected to GitHub where you have to confirm the access by your application.
