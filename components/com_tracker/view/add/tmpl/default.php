<?php
/**
 * User: elkuku
 * Date: 15.10.12
 * Time: 22:03
 */

// @todo move the "template" to ... a template ¿
$template = "
Please enter a description of the issue

## Test instructions

Please add some instructions on how to reproduce the issue

Example:

* Install Joomla! with sample data
* Browse to the backend and click...

## Test code
You may add some code if needed

```php
echo 'my issue';
```

## Screenshots
You may upload screenshots or reference external images here.

Example:

![Joomla! World Conference](http://conference.joomla.org/images/banners/general/728x90.jpg)
";

?>
<h1>Add a new Issue</h1>

<form>
    <h2>Description</h2>
	<div class="">
	<?php echo $this->editor->display('description', $template, '100%', 300, 10, 10, false, 'editor-comment', null, null, $this->editorParams); ?>
    </div
    <input class="btn btn-large btn-success" type="submit" value="Submit issue report"/>
</form>
