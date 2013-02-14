<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Getting params from template
$params = JFactory::getApplication()->getTemplate(true)->params;

$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$this->language = $doc->language;
$this->direction = $doc->direction;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->get('sitename');

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

// Add Stylesheets
$doc->addStyleSheet('templates/protostar/css/template.css');

// Load optional rtl Bootstrap css and Bootstrap bugfixes
JHtml::_('bootstrap.loadCss', false, $this->direction);

$user = JFactory::getUser();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="head" />
	<!--[if lt IE 9]>
		<script src="<?php echo $this->baseurl ?>/media/jui/js/html5.js"></script>
	<![endif]-->
</head>

<body class="site fluid <?php echo $option
	. ' view-' . $view
	. ($layout ? ' layout-' . $layout : ' no-layout')
	. ($task ? ' task-' . $task : ' no-task')
	. ($itemid ? ' itemid-' . $itemid : '');
?>">

	<!-- Body -->
	<div class="body">
		<div class="container-fluid">
			<div class="pull-right">
				<?php if($user->id) : ?>
					<?php echo JHtmlGithub::avatar($user, 20) ?>
					<?php echo JHtml::link('index.php?option=com_users&task=ghlogout', sprintf('Logout %s', $user->username)) ?>
				<?php else : ?>
					<?php echo JHtmlGithub::loginButton() ?>
				<?php endif; ?>
			</div>
			<!-- Header -->
			<div class="header">
				<div class="header-inner clearfix">
                    <a class="brand pull-left" href="<?php echo $this->baseurl; ?>">
                        <img src="<?php echo $this->baseurl ?>/templates/protostar/images/joomla.png" alt="Joomla" />
					</a>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span7">
					<ul class="nav nav-pills">
						<li class="<?php echo (!$option || 'com_tracker' == $option) ? 'active' : '' ?>">
							<a href="<?php echo JRoute::_('index.php?option=com_tracker'); ?>">Tracker</a>
						</li>
						<li class="<?php echo 'com_users' == $option ? 'active' : '' ?>">
							<a href="<?php echo JRoute::_('index.php?option=com_users'); ?>">Users</a>
						</li>
					</ul>
				</div>
				<div class="span5">
					<div class="btn-toolbar pull-right">
						<div class="btn-group">
							<jdoc:include type="modules" name="toolbar" style="no" />
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid">
				<div id="content" class="span12">
					<!-- Begin Content -->
					<jdoc:include type="message" />
					<jdoc:include type="component" />
					<!-- End Content -->
				</div>
			</div>
		</div>
	</div>
	<!-- Footer -->
	<div class="footer">
		<div class="container-fluid">
			<hr />
			<p class="pull-right"><a href="#top" id="back-top"><?php echo JText::_('TPL_PROTOSTAR_BACKTOTOP'); ?></a></p>
			<p>&copy; <?php echo $sitename; ?> <?php echo date('Y');?></p>
		</div>
	</div>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
