## Supported languages

A list of supported languages is stored in the `LanguageHelper` class.

To add a new language, add the following to the array of `$languages`:

```
'{code}' => [
	'iso' => '{ISO-Code}',
	'name' => '{Language name}',
	'display' => '{Language display name}'
],
```

* `{code}` The language code - e.g. `en-GB` - See: [languagecodes](https://chronoplexsoftware.com/myfamilytree/localisation/languagecodes.htm)
* `{ISO-Code}` The ISO code - e.g. `uk` loosely following the [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) standard.
* `{Language name}` The language name - e.g. `English` - usually in English.
* `{Language display name}` The text to display - e.g. `British English` - Should be in "native" characters. - See: [languagecodes](https://chronoplexsoftware.com/myfamilytree/localisation/languagecodes.htm)

## Update language selector flag images

* Download the icon pack from http://forum.tsgk.com/viewtopic.php?t=4921 and store it "somewhere"
* Issue the command `make languageflags` using the following parameters:
    * The path where the flag images are stored ("somewhere").
    * `--imagefile` (optional) path to store the result image.
    * `--cssfile` (optional) path to store the result CSS file.
