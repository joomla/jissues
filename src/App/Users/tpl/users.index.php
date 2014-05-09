<?php
// @codingStandardsIgnoreFile

/**
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

?>

<h1>Users List</h1>

<table>
	<tr>
		<th>ID</th>
		<th>Username</th>
	</tr>
	<?php foreach ($this->items as $item) : ?>
		<tr>
			<td><?php echo $item->id; ?></td>
			<td><?php echo $item->username; ?></td>
		</tr>
	<?php endforeach; ?>
</table>
