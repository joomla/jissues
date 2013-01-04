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
	<li><a href="#spice" data-toggle="tab">GitHub Spice</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="info">
		<p>Links:</p>
		<ul>
			<li><a href="http://daringfireball.net/projects/markdown/">http://daringfireball.net/projects/markdown/</a></li>
			<li><a href="https://github.com/wolfie/php-markdown">https://github.com/wolfie/php-markdown</a></li>
			<li><a href="http://wikipedia.org/wiki/Markdown">http://wikipedia.org/wiki/Markdown</a></li>
		</ul>
	</div>
    <div class="tab-pane" id="headers">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre># H1
## H2
### H3
#### H4
##### H5
###### H6</pre>
			</div>
			<div class="span6">
				<h1>H1</h1>
				<h2>H2</h2>
				<h3>H3</h3>
				<h4>H4</h4>
				<h5>H5</h5>
				<h6>H6</h6>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="lists">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>* A list
* Of
* Items

* A List
	* With a subitem (indent with 4 spaces)
* Nice..

1. A numbered list
2. Of
3. Items</pre>
			</div>
			<div class="span6">
				<ul>
					<li>A list</li>
					<li>Of</li>
					<li><p>Items</p></li>
					<li><p>A List</p>
						<ul>
							<li>With a subitem (indent with 4 spaces)</li>
						</ul>
					</li>
					<li><p>Nice..</p></li>
				</ul>
				<ol>
					<li>A numbered list</li>
					<li>Of</li>
					<li>Items</li>
				</ol>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="links">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>See: http://joomla.org for more information.

See: [The Joomla! home page](http://joomla.org) for more information.</pre>
			</div>
			<div class="span6">
				<p>See: <a href="http://joomla.org">http://joomla.org</a> for more information.</p>
				<p>See: <a href="http://joomla.org">The Joomla! home page</a> for more information.</p>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="emphasized">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>*emphasis* or _emphasis_  (e.g., _italics_)

**strong emphasis** or __strong emphasis__ (e.g., **boldface**)</pre>
			</div>
			<div class="span6">
				<p><em>emphasis</em> or <em>emphasis</em>  (e.g., <em>italics</em>)</p>
				<p><strong>strong emphasis</strong> or <strong>strong emphasis</strong> (e.g., <strong>boldface</strong>)</p>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="code">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>Some text with `some code` inside.

	line 1 of code indented by 4 spaces
	line 2 of code indented by 4 spaces
	line 3 of code indented by 4 spaces

Some text with ```some fenced code``` inside.

```
line 1 of fenced code
line 2 of fenced code
line 3 of fenced code
```

Syntax highlighting provided by [GitHub Flavored Markdown](http://github.github.com/github-flavored-markdown/)

```php
protected function parse($raw)
{
	$o = new stdClass;
	$o->text = $raw;

	$this->dispatcher->trigger('onContentPrepare', array('com_tracker.markdown', &$o, new JRegistry));

	echo $o->text;
}
```</pre>
			</div>
			<div class="span6">
				<p>Some text with <code>some code</code> inside.</p>
				<pre>
<code>line 1 of code indented by 4 spaces
line 2 of code indented by 4 spaces
line 3 of code indented by 4 spaces</code></pre>

				<p>Some text with <code>some fenced code</code> inside.</p>

				<pre>
<code>line 1 of fenced code
line 2 of fenced code
line 3 of fenced code</code></pre>

				<p>Syntax highlighting provided by <a href="http://github.github.com/github-flavored-markdown/">GitHub Flavored Markdown</a></p>

				<div class="highlight">
					<pre>
<span class="k">protected</span> <span class="k">function</span> <span class="nf">parse</span><span class="p">(</span><span class="nv">$raw</span><span class="p">)</span>
<span class="p">{</span>
	<span class="nv">$o</span> <span class="o">=</span> <span class="k">new</span> <span class="k">stdClass</span><span class="p">;</span>
	<span class="nv">$o</span><span class="o">-&gt;</span><span class="na">text</span> <span class="o">=</span> <span class="nv">$raw</span><span class="p">;</span>

	<span class="nv">$this</span><span class="o">-&gt;</span><span class="na">dispatcher</span><span class="o">-&gt;</span><span class="na">trigger</span><span class="p">(</span><span class="s1">'onContentPrepare'</span><span class="p">,</span> <span class="k">array</span><span class="p">(</span><span class="s1">'com_tracker.markdown'</span><span class="p">,</span> <span class="o">&amp;</span><span class="nv">$o</span><span class="p">,</span> <span class="k">new</span> <span class="nx">JRegistry</span><span class="p">));</span>

	<span class="k">echo</span> <span class="nv">$o</span><span class="o">-&gt;</span><span class="na">text</span><span class="p">;</span>
<span class="p">}</span></pre>
				</div>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="blockquotes">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>> "This entire paragraph of text will be enclosed in an HTML blockquote element.
Blockquote elements are reflowable. You may arbitrarily
wrap the text to your liking, and it will all be parsed
into a single blockquote element."</pre>
			</div>
			<div class="span6">
				<blockquote>
					<p>"This entire paragraph of text will be enclosed in an HTML blockquote element.<br>
					Blockquote elements are reflowable. You may arbitrarily<br>
					wrap the text to your liking, and it will all be parsed<br>
					into a single blockquote element."</p>
				</blockquote>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="hrs">
		<div class="alert alert-info">Horizontal rules - Please use with care..</div>
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>* * *
***
*****
- - -</pre>
			</div>
			<div class="span6">
				<hr>
				<hr>
				<hr>
				<hr>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="tables">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>First Header  | Second Header
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
| `destroy()`   | **Destroy your computer!**     |</pre>
			</div>
			<div class="span6">
                <table>
                    <tr>
                        <th>First Header</th>
                        <th>Second Header</th>
                    </tr>
                    <tr>
                        <td>Content Cell</td>
                        <td>Content Cell</td>
                    </tr>
                    <tr>
                        <td>Content Cell</td>
                        <td>Content Cell</td>
                    </tr>
                </table>
                <h3>Alignment</h3>

                <p>If you wish, you can add a leading and tailing pipe to each line of the table.</p>

                <p>You can specify alignement for each column by adding colons to separator lines. A colon at the left of the separator line will make the column left-aligned; a colon on the right of the line will make the column right-aligned; colons at both side means the column is center-aligned.</p>

                <table>
                    <tr>
                        <th>Item</th>
                        <th align="right">Value</th>
                    </tr>
                    <tr>
                        <td>Computer</td>
                        <td align="right">$1600</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td align="right">$12</td>
                    </tr>
                    <tr>
                        <td>Pipe</td>
                        <td align="right">$1</td>
                    </tr>
                </table>
                <h3>Formatting</h3>

                <p>You can apply span-level formatting to the content of each cell using regular Markdown syntax:</p>

                <table>
                    <tr>
                        <th>Function name</th>
                        <th>Description</th>
                    </tr>
                    <tr>
                        <td><code>help()</code></td>
                        <td>Display the help window.</td>
                    </tr>
                    <tr>
                        <td><code>destroy()</code></td>
                        <td><strong>Destroy your computer!</strong></td>
                    </tr>
                </table>
            </div>
		</div>
    </div>
    <div class="tab-pane" id="images">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>![Joomla! World Conference](http://conference.joomla.org/images/banners/general/728x90.jpg)</pre>
			</div>
			<div class="span6">
				<p>
					<a target="_blank" href="https://a248.e.akamai.net/camo.github.com/b96dc6ce0f6fa1344e550f2c1c68507c8a627944/687474703a2f2f636f6e666572656e63652e6a6f6f6d6c612e6f72672f696d616765732f62616e6e6572732f67656e6572616c2f3732387839302e6a7067">
						<img src="https://a248.e.akamai.net/camo.github.com/b96dc6ce0f6fa1344e550f2c1c68507c8a627944/687474703a2f2f636f6e666572656e63652e6a6f6f6d6c612e6f72672f696d616765732f62616e6e6572732f67656e6572616c2f3732387839302e6a7067" alt="Joomla! World Conference" style="max-width:100%;">
					</a>
				</p>
			</div>
		</div>
    </div>
    <div class="tab-pane" id="spice">
		<div class="row-fluid">
			<div class="span6">
				<h3>Code</h3>
			</div>
			<div class="span6">
				<h3>Output</h3>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span6">
				<pre>* SHA: 935fef6f37e2c049ed2534b11c0d28e18e5e3f9c
* User@SHA: mbabker@935fef6f37e2c049ed2534b11c0d28e18e5e3f9c
* User/Project@SHA: mbabker/jissues@935fef6f37e2c049ed2534b11c0d28e18e5e3f9c
* #Num: #1
* User/#Num: mbabker#1
* User/Project#Num: mbabker/jissues#1
* It is in no way the fault of @mbabker that you are looking at this page here and now...</pre>
			</div>
			<div class="span6">
                <ul>
                    <li>SHA:
                        <a href="https://github.com/JTracker/jissues/commit/935fef6f37e2c049ed2534b11c0d28e18e5e3f9c" class="commit-link"><tt>935fef6</tt></a>
                    </li>
                    <li>User@SHA: <a href="https://github.com/mbabker/jissues/commit/935fef6f37e2c049ed2534b11c0d28e18e5e3f9c" class="commit-link">mbabker@<tt>935fef6</tt></a>
                    </li>
                    <li>User/Project@SHA:
                        <a href="https://github.com/mbabker/jissues/commit/935fef6f37e2c049ed2534b11c0d28e18e5e3f9c" class="commit-link">mbabker/jissues@<tt>935fef6</tt></a>
                    </li>
                    <li>#Num: <a href="https://github.com/JTracker/jissues/issues/1" class="issue-link" title="Sorting and Filtering options">#1</a>
                    </li>
                    <li>User/#Num: mbabker#1</li>
                    <li>User/Project#Num: mbabker/jissues#1</li>
                    <li>It is in no way the fault of
                        <a href="https://github.com/mbabker" class="user-mention">@mbabker</a> that you are looking at this page here and now...
                    </li>
                </ul>
            </div>
		</div>
    </div>
</div>
