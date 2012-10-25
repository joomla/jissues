<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var TrackerViewEditHtml $this */

defined('_JEXEC') or die;
?>

<?php foreach ($this->fields as $field) : ?>
<tr>
	<th><?php echo $field->title; ?></th>
	<td><?php echo $this->fieldList[$field->alias]; ?></td>
</tr>
<? endforeach; ?>
