<?php
/*
{# Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved. #}
{# GNU General Public License version 2 or later; see LICENSE.txt #}
*/
?>
<div class="alert alert-error">

	<h4><?= get_class($this->exception) ?></h4>

	<p><?= $this->exception->getMessage() ?></p>

	<?php if ($this->message) : ?>
		<p><?= $this->message ?></p>
	<?php endif ?>
</div>

<?php if (JDEBUG) : ?>
	In: <a
		href="xdebug://<?= $this->exception->getFile() ?>@<?= $this->exception->getLine() ?>"><?= $this->exception->getFile() ?>
		@<?= $this->exception->getLine() ?></a>
	<table class="table table-bordered table-hover">
		<tr>
			<th>File</th>
			<th>Line</th>
			<th>Class->Method()</th>
		</tr>
		<?php foreach ($this->exception->getTrace() as $stack) : ?>
			<tr>
				<td><a href="xdebug://<?= $stack['file'] ?>@<?= $stack['line'] ?>"><?= basename($stack['file']) ?></a>
				</td>
				<td><?= $stack['line'] ?></td>
				<td><?= $stack['class'] ?><?= $stack['type'] ?><?= $stack['function'] ?>()</td>
			</tr>
		<?php endforeach ?>
	</table>
<?php endif ?>
