<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var TrackerViewMarkdowntestpageHtml $this */

defined('_JEXEC') or die;
?>
<h1>Markdown TEST Page</h1>
<ul class="nav nav-tabs">
	<li class="active"><a href="#info" data-toggle="tab">Info</a></li>
	<li><a href="#headers" data-toggle="tab">Headers</a></li>
	<li><a href="#lists" data-toggle="tab">Lists</a></li>
	<li><a href="#links" data-toggle="tab">Links</a></li>
	<li><a href="#emphasized" data-toggle="tab">Emphasized</a></li>
	<li><a href="#code" data-toggle="tab">Code</a></li>
	<li><a href="#blockquotes" data-toggle="tab">Blockquotes</a></li>
	<li><a href="#hrs" data-toggle="tab">HRs</a></li>
	<li><a href="#tables" data-toggle="tab">Tables</a></li>
	<li><a href="#images" data-toggle="tab">Images</a></li>
	<li><a href="#footnotes" data-toggle="tab">Footnotes</a></li>
	<li><a href="#spice" data-toggle="tab">@Spice</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="info">
<?php ob_start(); ?>
Links:

* http://daringfireball.net/projects/markdown/
* https://github.com/wolfie/php-markdown
* http://wikipedia.org/wiki/Markdown

<?php echo $this->parse(ob_get_clean()); ?>
	</div>
    <div class="tab-pane" id="headers">
<?php ob_start(); ?>
# H1
## H2
### H3
#### H4
##### H5
###### H6
####### H7 ```=;)```

Reference:

* [My Header](#myheader)

## This is my header {#myheader}
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="lists">
<?php ob_start(); ?>
* A list
* Of
* Items

* A List
    * With a subitem (indent with 4 spaces)
* Nice..

1. A numbered list
2. Of
3. Items

Orange
:   The fruit of an evergreen tree of the genus Citrus.

Apple
:   Pomaceous fruit of plants of the genus Malus in
the family Rosaceae.
:   An american computer company.
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="links">
<?php ob_start(); ?>
See: http://joomla.org for more information.

See: [The Joomla! home page](http://joomla.org) for more information.
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="emphasized">
<?php ob_start(); ?>
*emphasis* or _emphasis_  (e.g., _italics_)

**strong emphasis** or __strong emphasis__ (e.g., **boldface**)
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="code">
<?php ob_start(); ?>
Some text with `some code` inside.

    line 1 of code indented by 4 spaces
    line 2 of code indented by 4 spaces
    line 3 of code indented by 4 spaces

Some text with ```some fenced code``` inside.

```
line 1 of fenced code
line 2 of fenced code
line 3 of fenced code
```

Syntax highlighting provided by [Luminous](https://github.com/markwatkinson/luminous)

```php
protected function parse($raw)
{
	$o = new stdClass;
	$o->text = $raw;

	$this->dispatcher->trigger('onContentPrepare', array('com_tracker.markdown', &$o, new JRegistry));

	echo $o->text;
}
```
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="blockquotes">
<?php ob_start(); ?>
> "This entire paragraph of text will be enclosed in an HTML blockquote element.
Blockquote elements are reflowable. You may arbitrarily
wrap the text to your liking, and it will all be parsed
into a single blockquote element."

<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="hrs">
<p>Horizontal rules - Please use with care..</p>
<?php ob_start(); ?>
* * *
***
*****
- - -
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="tables">
<?php ob_start(); ?>
First Header  | Second Header
------------- | -------------
Content Cell  | Content Cell
Content Cell  | Content Cell

### Alignment
If you wish, you can add a leading and tailing pipe to each line of the table.

You can specify alignement for each column by adding colons to separator lines. A colon at the left of the separator line will make the column left-aligned; a colon on the right of the line will make the column right-aligned; colons at both side means the column is center-aligned.

| Item      | Value |
| --------- | -----:|
| Computer  | $1600 |
| Phone     |   $12 |
| Pipe      |    $1 |

### Formatting
You can apply span-level formatting to the content of each cell using regular Markdown syntax:

| Function name | Description                    |
| ------------- | ------------------------------ |
| `help()`      | Display the help window.       |
| `destroy()`   | **Destroy your computer!**     |

<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="images">
<?php ob_start(); ?>
![Joomla! World Conference](http://conference.joomla.org/images/banners/general/728x90.jpg)
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="footnotes">
<?php ob_start(); ?>
That's some text with a footnote.[^1]

Here goes some text....

[^1]: And that's the footnote.
<?php echo $this->parse(ob_get_clean(), true); ?>
    </div>
    <div class="tab-pane" id="spice">
<?php ob_start(); ?>
* SHA: be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* User@SHA: elkuku@be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* User/Project@SHA: elkuku/EasyCreator@be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* #Num: #1
* User/#Num: elkuku#1
* User/Project#Num: elkuku/EasyCreator#1
* Feel free to blame @elkuku for this crap !
<?php echo $this->parse(ob_get_clean(), true); ?>
	</div>
</div>
