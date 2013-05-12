<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\Tracker\Components\System\HTML\HtmlConfig;

?>
<h1>Edit Config</h1>

<form method="post" action="saveconfig" class="configform">
	<ul>
		<?php foreach ($this->config as $group => $fields) : ?>
			<?php if (is_object($fields)) : ?>
				<h2><?php echo ucfirst($group); ?></h2>
				<?php foreach ($fields as $name => $value) : ?>
					<ul>
						<li>
							<?php echo HtmlConfig::field($name, $value, $group); ?>
						</li>
					</ul>
				<?php endforeach; ?>
			<?php else : ?>
				<li>
					<?php echo HtmlConfig::field($group, $fields); ?>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>

	<input type="submit" value="Save" class="btn btn-success"/>

</form>
