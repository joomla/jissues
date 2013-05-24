## Config editor
There is a "system component" that currently displays a config editor using simple text fields where you can fill in the values.
Saving is not provided yet. When hitting the save button the config is written to the screen where you might copy&paste it ;)
If you are on PHP < 5.4 there will be only a "compressed" version of the JSON string (a "pretty print" function could be written).
TBH - I only wrote this because it requires very little code and at some point we (or whoever uses a JSON based config file) might think about an UI for editing it.
