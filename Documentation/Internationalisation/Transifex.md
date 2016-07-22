## Translations via Transifex

The Issue Tracker proudly welcomes translations contributed via [Transifex](https://www.transifex.com).  The project can be found at [https://opentranslators.transifex.com/projects/p/jtracker/](https://opentranslators.transifex.com/projects/p/jtracker/).

### Application configuration

In order to interface with Transifex, the [BabDev Transifex Library](https://github.com/BabDev/Transifex-API) is implemented which includes a wrapper for the Transifex API.  Within the application, a `transifex` array exists within the overall JSON object with three parameters:

* `username` - The username of your Transifex account
* `password` - The password of your Transifex account
* `project` - The alias of your Transifex project (the part of the project's main URL after `projects/p/`

### Transifex Guidelines

The following are basic guidelines for maintaining the Transifex project.

#### Adding languages

Languages should utilize the full language code versus a shortcode.  For example, `de_DE` (as listed in Transifex) should be used for the German translations.  All source languages should be uploaded with the language code `en_GB`.

#### Naming convention

Resources should follow a `"extension" "domain"` naming convention for both the name and alias.  For example, the "core" translations (found at [/src/JTracker/g11n](/src/JTracker/g11n)) use the extension "JTracker" and domain "Core".  The resource should therefore be named "JTracker Core" with an alias of "jtracker-core".  This enables a predictable naming convention in our synchronization scripts.

#### Pushing to Transifex

A CLI command, `update languagefiles --provider=transifex`, pushes all of the language templates to Transifex which updates the source language for each resource.  The `Debug` template must be manually updated on Transifex because the Transifex object is not properly supporting sending it.  **TODO** Debug this.

#### Pulling from Transifex

A CLI command, `get languagefiles --provider=transifex` retrieves all translations from Transifex and updates the `.po` files in the filesystem with these resources.  Only languages listed in the `languages` array in the configuration JSON are retrieved.

* **TODO** Establish guidance on when a language is added to the configuration.
