<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_toolbar
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<ul class="nav menu nav-pills">
	<li class="<?php echo (!$option || 'com_tracker' == $option) ? 'current active' : '' ?>">
		<a href="<?php echo JRoute::_('index.php?option=com_tracker'); ?>">Tracker</a>
	</li>
	<li class="<?php echo 'com_users' == $option ? 'current active' : '' ?>">
		<a href="<?php echo JRoute::_('index.php?option=com_users'); ?>">Users</a>
	</li>
</ul>
