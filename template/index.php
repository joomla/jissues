<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$doc = JFactory::getDocument();

// Add Stylesheets
$doc->addStyleSheet('template/css/template.css');

// Load the JavaScript behaviors
JHtml::_('bootstrap.framework');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
	<head>
		<jdoc:include type="head" />
	</head>
	<body>
		<!-- Header -->
		<div class="header">
			<img src="<?php echo $this->baseurl ?>/template/images/joomla.png" alt="Joomla" />
		</div>
		<!-- Container -->
		<div class="container">
			<div class="row-fluid">
				<div class="span12">
					<jdoc:include type="component" />
				</div>
			</div>
		</div>
	</body>
</html>
