## GitHub Authentication

GitHub limits requests to its API to 60 per hour for unauthenticated requests, and to 5000 per hour for requests authenticated with a personal access token or oAuth.

For the initial import of issues and issue comments to the database, for webhooks and for anonymous visitors the tracker uses the GitHub accounts from the configuration, so these need to be authenticated to avoid exceeding the rate limit.

#### Personal access tokens
GitHub no longer accepts account passwords for API requests. Create a personal access token for each account instead:

* Go to "Settings" - "Developer settings" - "Personal access tokens" on GitHub.
* Create a classic token with the `public_repo` scope (or `repo` for private repositories). Fine-grained tokens only work for organisation repositories if the organisation allows them.
* Give the token an expiry date and make sure it is renewed before it expires.

Add the tokens to the `github.accounts` list in `etc/config.json`:

```json
"github": {
    "accounts": [
        {
            "username": "first-bot",
            "token"   : "ghp_..."
        },
        {
            "username": "second-bot",
            "token"   : "ghp_..."
        }
    ]
}
```

The first account is used by default. The CLI `get` commands switch to the next account when the rate limit of the current one runs low. The `username` is only used for log messages when a token is set. A `password` entry is still supported, but only works if it contains a personal access token.

#### Project edit bot
Each project can have an edit bot account which is used for webhooks and for changes that the logged in user may not be allowed to make on GitHub (e.g. labels and milestones). Enter its user name and personal access token in the project settings. The account needs triage or write access to the repository.

#### oAuth login
Users can log in with their GitHub account using oAuth. Requests made on behalf of a logged in user use their oAuth token and count against their own rate limit.

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
