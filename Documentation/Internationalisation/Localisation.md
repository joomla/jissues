## Localisation

aka [The option #3 from joomla/jissues#124](https://github.com/joomla/jissues/pull/124)

### Translation 101

After you changed the code in your PHP or Twig files you should run

* `bin/jtracker make langtemplates`<br />
This will create/update the `pot` language templates.<br />
Those may be handed over to the translaters.

Additionally you can use the `extension` argument to create/update the `pot` language templates for the specific extension only

* `bin/jtracker make langtemplates --extension=Tracker`<br />

If you want to translate yourself run the following command:

* `bin/jtracker make langfiles`<br />
This will create/update the `po` language files according to the templates created in the previous step.

After changing the `po` files you have to clean the cache to "see" the translations on the site.
This can be done by simply deleting the `/cache/g11n` directory or, if you are logged in with an admin account, using the "clean cache" command from the "System" menu.

### The workflow for JIssues repository

```
bin/jtracker make langtemplates
git commit -am "Update language templates"
bin/jtracker update languagefiles --provider=crowdin
bin/jtracker get languagefiles --provider=crowdin
git commit -am "Fetch updated language files"
bin/jtracker clear languagecache
git push
```

#### How it works (The option #3)

I might have stated before that I'm a very lazy person. So I like to spend my time creating scripts that do the "ugly work" for me. - Like the creation and maintainance of language files :P

The proposed solution to the above mentioned problem implemnents a (still) experimental language handler, that has not been tested out there in the wild.

The basic idea is:

* Read language files in multiple formats.
* Extract the key/value pairs.
* Store the result in a "permanent cache" to speed things up.

The language file format to use in JTracker will be `po` ([gettext](http://en.wikipedia.org/wiki/Gettext)), the result is stored to a "native PHP array" which is written to a text file. Other options can be explored.

#### 3a) Required changes

Changes to hard coded strings in Twig templates and PHP code:

```php
// template.twig
<label>
    My String

    // change to:
    {{ translate("My String") }}

    // **OR** just use a pipe and a shortcut:
    {{ "My String"|_ }}
</label>
```

In PHP you would use the global function with an easy to remember name; `g11n3t` (*)

```php
echo g11n3t('My String');
```

... and go home !

(*) **btw**: `g11n3t` means `globalizationtext`. If you don't like it, you may create your own alias ;)


The next step would be the creation of the language files.

#### 3b) Language template creation

For gettext files, you first create a **template** that contains all the **keys** and **empty values**.
These templates are used to create and update the localised language files.
The file extension for template files is `.pot`.

```
# Extension.pot

msgid "My String"
msgstr ""
```

These files can be created and maintained manually, however... I'm a lazy person (did I say that before ? )

The gettext utility [xgettext](http://linux.die.net/man/1/xgettext) can read a wide range of code languages and supports a custom function name.
It supports over 20 languages officially, others just "work" (like JavaScript can be parsed as Python...) but the only unsupported language I know is Twig :(
Fortunately this is a [known issues](https://github.com/fabpot/Twig-extensions/blob/master/doc/i18n.rst), so the solution is to compile the templates and then run xgettext over the generated PHP code.

There is a new script that just collects all relevant files and passes them, along with some options, to xgettext:

```
bin/jtracker make langtemplates
```

Will automatically generate the language templates for the core JTracker application, the JTracker template as well as all the Apps.

Those language templates, once created, are now ready to hand over to the translators or send them to an online translation service.

Job finished :)

#### 3c) Localise It !

To actually "see" the site in different languages, you have to create a file that contains the localised strings for every language.
The extension for language files is `.po`.

For example a german language file might look like this:
```
# de-DE.Extension.po

msgid "My String"
msgstr "Meine Zeichenkette"
```

A chinese language file might look like this (Google says..):
```
# zh-CN.Extension.po

msgid "My String"
msgstr "我的字符串"
```

and so on...

Translators may notice here, that you always **see** the original in clear text above the translation. -- If you plan to handle the translations manually...

While you can also create those files manually, the gettext tools [msginit](http://linux.die.net/man/1/msginit) and [msgmerge](http://linux.die.net/man/1/msgmerge) can create and update language files from a given template - So why not use them (remember: me lazy...)

```
bin/jtracker make langfiles
```

will create language files for the core, the template and all extensions (Apps) in all defined languages.

What else ?

#### System requirements

To **manually** create and manage your language file(s) you will need:
* Your hand(s).

To have your language files created and managed **automatically** you will need:
* [gnu gettext](http://directory.fsf.org/wiki/Gettext) - from which you will only need it's utilities.

The gettext utilities should be available or installable on all *nix based systems, as well as some sons/daugthers and parents (like BSDs and apple stuff).
If you are stuck on windows, your best bet may be [cygwin](http://www.cygwin.com/) (as always). There is also [MinGW](http://www.mingw.org/), a [sourceforge project](http://sourceforge.net/projects/gettext/), as well as [this site](http://franco-bez.4lima.de/index.php?option=com_content&view=article&id=55&Itemid=64&lang=en).
I have not tried any of the above currently beside my own linux box, but I believe that it should be no problem for a windows developer with decent skills to modify the script ;)

#### Known issues

* There is one big FAT issue currently: Internally all strings are contained in a single array. Meaning that you can not translate the same key in two different ways in the same page call.<br />I believe that our application is "small enough", so this won't really be an issue.<br />There is a solution deep down in my head, but it hasn't been translated to code yet ;) WIP
* JavaScript translations and pluralizations are supported but not implemented yet. WIP
* Performance... This will be the last time that I mention that I'm lazy but... to avoid ugly escaping/unescaping of quotes, I simply base64 encode and decode the string and md5 encode the key which is, I admit that, very very time consuming W-I-P...

#### Usage in the virtual environment

The [virtual environment](../Development/Virtual-Test-Server.md) already has the gettext package added, so creating and updating language files can be done from here, in case a developer can/will not install gettext on his/her operating system.

It goes like this:

```
vagrant ssh
cd /vagrant
bin/jtracker make langtemplates
bin/jtracker make langfiles
```
It would be nice if a "non-Linux" user could test this :wink:

#### Refs

* https://github.com/elkuku/g11n - The experimental language handler oO
