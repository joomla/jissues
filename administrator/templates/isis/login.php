<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app = JFactory::getApplication();
$doc = JFactory::getDocument();

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.tooltip');

// Add Stylesheets
$doc->addStyleSheet('templates/' .$this->template. '/css/template.css');

// If Right-to-Left
if ($this->direction == 'rtl') :
	$doc->addStyleSheet('../media/jui/css/bootstrap-rtl.css');
endif;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->getCfg('sitename');

// Check if debug is on
$config = JFactory::getConfig();
$debug  = (boolean) $config->get('debug');
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<jdoc:include type="head" />
	<script type="text/javascript">
		window.addEvent('domready', function () {
			document.getElementById('form-login').username.select();
			document.getElementById('form-login').username.focus();
		});
	</script>
	<style type="text/css">
		/* Responsive Styles */
		@media (max-width: 480px) {
			.view-login .container{
				margin-top: -170px;
			}
			.btn{
				font-size: 13px;
				padding: 4px 10px 4px;
			}
		}
		<?php if ($debug) : ?>
			.view-login .container {
				position: static;
				margin-top: 20px;
				margin-left: auto;
				margin-right: auto;
			}
			.view-login .navbar-fixed-bottom {
				display: none;
			}
		<?php endif; ?>
	</style>
	<!--[if lt IE 9]>
		<script src="../media/jui/js/html5.js"></script>
	<![endif]-->
</head>

<body class="site <?php echo $option . " view-" . $view . " layout-" . $layout . " task-" . $task . " itemid-" . $itemid . " ";?>">
	<!-- Container -->
	<div class="container">
		<div id="content">
			<!-- Begin Content -->
			<div id="element-box" class="login well">
				<img src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template ?>/images/joomla.png" alt="Joomla!" />
				<hr />
				<jdoc:include type="message" />
				<jdoc:include type="component" />
			</div>
			<noscript>
				<?php echo JText::_('JGLOBAL_WARNJAVASCRIPT') ?>
			</noscript>
			<!-- End Content -->
		</div>
	</div>
	<div class="navbar navbar-fixed-bottom hidden-phone">
		<p class="pull-right">&copy; <?php echo $sitename; ?> <?php echo date('Y');?></p>
		<a class="login-joomla" href="http://www.joomla.org" class="hasTooltip" title="<?php echo JText::_('TPL_ISIS_ISFREESOFTWARE');?>">Joomla!&#174;</a>
		<a href="<?php echo JURI::root(); ?>" class="pull-left"><i class="icon-share icon-white"></i> <?php echo JText::_('COM_LOGIN_RETURN_TO_SITE_HOME_PAGE') ?></a>
	</div>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
