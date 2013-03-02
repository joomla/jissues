<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.protostar
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/* @var $app JApplicationSite */
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

if($task == "edit" || $layout == "form" )
{
	$fullWidth = 1;
}
else
{
	$fullWidth = 0;
}

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

// Add Stylesheets
$doc->addStyleSheet('templates/'.$this->template.'/css/template.css');

// Load optional rtl Bootstrap css and Bootstrap bugfixes
JHtmlBootstrap::loadCss($includeMaincss = false, $this->direction);

// Add current user information
$user = JFactory::getUser();

// Adjusting content width
if ($this->countModules('position-7') && $this->countModules('position-8'))
{
	$span = "span6";
}
elseif ($this->countModules('position-7') && !$this->countModules('position-8'))
{
	$span = "span9";
}
elseif (!$this->countModules('position-7') && $this->countModules('position-8'))
{
	$span = "span9";
}
else
{
	$span = "span12";
}

// Logo file or site title param
if ($this->params->get('logoFile'))
{
	$logo = '<img src="'. JURI::root() . $this->params->get('logoFile') .'" alt="'. $sitename .'" />';
}
elseif ($this->params->get('sitetitle'))
{
	$logo = '<span class="site-title" title="'. $sitename .'">'. htmlspecialchars($this->params->get('sitetitle')) .'</span>';
}
else
{
	$logo = '<span class="site-title" title="'. $sitename .'">'. $sitename .'</span>';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="blue" />
	<jdoc:include type="head" />
	<?php
	// Use of Google Font
	// TRACKER MOD: Hard coded use of Google Font
	?>
		<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
		<style type="text/css">
			h1,h2,h3,h4,h5,h6,.site-title{
				font-family: 'Open Sans', sans-serif;
			}
		</style>
	<!--[if lt IE 9]>
		<script src="<?php echo $this->baseurl ?>/media/jui/js/html5.js"></script>
	<![endif]-->
</head>

<body class="site <?php echo $option
	. ' view-' . $view
	. ($layout ? ' layout-' . $layout : ' no-layout')
	. ($task ? ' task-' . $task : ' no-task')
	. ($itemid ? ' itemid-' . $itemid : '')
	. ($this->params->get('fluidContainer') ? ' fluid' : '');
?>">
	<!-- Top Nav -->
	<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
					<div class="nav-collapse">
						<ul id="nav-joomla" class="nav ">
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span aria-hidden="true" class="icon-joomla"></span> Joomla! <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li class="nav-header"><span>Recent News</span></li>
								<li><a href="http://www.joomla.org/announcements.html">Announcements</a></li>
								<li><a href="http://community.joomla.org/blogs/community.html">Blogs</a></li>
								<li><a href="http://magazine.joomla.org/">Magazine</a></li>
								<li class="divider"><span></span></li>
								<li class="nav-header"><span>Support Joomla!</span></li>
								<li><a href="http://shop.joomla.org/">Shop Joomla Gear</a></li>
								<li><a href="http://opensourcematters.org/support-joomla.html">Contribution</a></li>
							</ul>
							</li>
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">About <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a href="http://www.joomla.org/about-joomla.html">About Joomla!</a>
									<li><a href="http://www.joomla.org/about-joomla.html">The Software</a></li>
									<li><a href="http://www.joomla.org/about-joomla/the-project.html">The Project</a></li>
									<li><a href="http://www.joomla.org/about-joomla/the-project/leadership-team.html">Leadership</a></li>
									<li><a href="http://opensourcematters.org/index.php">Open Source Matters</a></li>
								</ul>
							</li>
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Community <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a href="http://people.joomla.org/">Joomla! People Site</a></li>
									<li><a href="http://community.joomla.org/events.html">Joomla! Events</a></li>
									<li><a href="http://community.joomla.org/connect.html">Joomla! User Groups</a></li>
									<li><a href="http://community.joomla.org/user-groups.html">Joomla! Connect</a></li>
								</ul>
							</li>
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Support <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a href="http://forum.joomla.org/">Joomla! Forum</a></li>
									<li><a href="http://docs.joomla.org/">Joomla! Documentation</a></li>
									<li><a href="http://resources.joomla.org/">Joomla! Resources</a></li>
									<li><a href="http://www.joomla.org/mailing-lists.html">Joomla! Mailing Lists</a></li>
								</ul>
							</li>
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Extend <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li class="active"><a href="http://extensions.joomla.org/">Extensions Directory</a></li>
									<li><a href="http://extensions.joomla.org/">Extension Directory</a></li>
									<li><a href="http://community.joomla.org/showcase/">Showcase Directory</a></li>
									<li><a href="http://resources.joomla.org/">Resource Directory</a></li>
									<li><a href="http://community.joomla.org/translations.html">Translations</a></li>
									<li><a href="http://ideas.joomla.org/">Idea Pool</a></li>
								</ul>
							</li>
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Developers <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a href="http://developer.joomla.org/">Developer Site</a></li>
									<li><a href="http://docs.joomla.org/">Documentation</a></li>
									<li><a href="http://ux.joomla.org/">Joomla! User Experience</a></li>
									<li><a href="http://docs.joomla.org/Bug_Squad">Joomla! Bug Squad</a></li>
									<li><a href="http://api.joomla.org/">Joomla! API</a></li>
									<li><a href="http://joomlacode.org/">JoomlaCode</a></li>
									<li><a href="https://github.com/joomla/joomla-platform">Joomla! Platform</a></li>
								</ul>
							</li>
						</ul>
						<ul id="nav-international" class="nav pull-right">
							<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span aria-hidden="true" class="icon-comments"></span></a>
								<ul class="dropdown-menu">
									<li><a href="http://community.joomla.org/translations.html">Translations</a></li>
									<li><a href="http://multilingual-joomla-demo.cloudaccess.net/">Multilingual Demo</a></li>
									<li><a href="http://docs.joomla.org/Translations_Working_Group">Translation Working Group</a></li>
									<li><a href="http://forum.joomla.org/viewforum.php?f=11">Translations Forum</a></li>
								</ul>
							</li>
						</ul>
						<form id="nav-search" class="navbar-search pull-right">
							  <input type="text" class="search-query" placeholder="Search">
						</form>

					</div>
					<!--/.nav-collapse -->
				</div>
			</div>
	</nav>
	<!-- Header -->
	<header class="header">
		<div class="container">
			<div class="row-fluid">
				<div class="span7">
					<h1 class="page-title"><?php echo JHtml::_('string.truncate', $sitename, 40, false, false);?></h1>
				</div>
				<div class="span5">
					<div class="btn-toolbar">
						<div class="btn-group">
							<a href="http://www.joomla.org/download.html" class="btn btn-large btn-warning">Download <span class="hidden-tablet">Joomla</span></a>
							<a class="btn btn-large btn-warning dropdown-toggle" data-toggle="dropdown">
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu">
								<li><a href="http://www.joomla.org/download.html">Joomla 2.5 (Long term release)</a></li>
								<li><a href="http://www.joomla.org/download.html">Joomla 3.0 (Short term release)</a></li>
							</ul>
						</div>
						<div class="btn-group">
							<a href="http://demo.joomla.org/" class="btn btn-large btn-primary">Demo <span class="hidden-tablet">Joomla</span></a>
							<a class="btn btn-large btn-primary dropdown-toggle" data-toggle="dropdown">
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu">
								<li><a href="http://joomla25.cloudaccess.net/">Joomla 2.5 Demo</a></li>
								<li><a href="http://joomla30.cloudaccess.net/">Joomla 3.0 Demo</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<div class="subnav" data-spy="affix" data-offset-top="68">
		<div class="container">
			<jdoc:include type="modules" name="position-1" style="none" />
		</div>
	</div>
	<!-- Body -->
	<div class="body">
		<div class="container<?php if ($this->params->get('fluidContainer')) { echo "-fluid"; } ?>">
			<div class="pull-right">
				<jdoc:include type="modules" name="toolbar" style="no" />
			</div>
			<jdoc:include type="modules" name="banner" style="xhtml" />
			<div class="row-fluid">
				<?php if ($this->countModules('position-8')): ?>
				<!-- Begin Sidebar -->
				<div id="sidebar" class="span3">
					<div class="sidebar-nav">
						<jdoc:include type="modules" name="position-8" style="xhtml" />
					</div>
				</div>
				<!-- End Sidebar -->
				<?php endif; ?>
				<div id="content" class="<?php echo $span;?>">
					<!-- Begin Content -->
					<jdoc:include type="modules" name="position-3" style="xhtml" />
					<jdoc:include type="message" />
					<jdoc:include type="component" />
					<jdoc:include type="modules" name="position-2" style="none" />
					<!-- End Content -->
				</div>
				<?php if ($this->countModules('position-7')) : ?>
				<div id="aside" class="span3">
					<!-- Begin Right Sidebar -->
					<jdoc:include type="modules" name="position-7" style="well" />
					<!-- End Right Sidebar -->
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<!-- Footer -->
	<div class="footer center">
		<div class="container<?php if ($this->params->get('fluidContainer')) { echo "-fluid"; } ?>">
			<hr />
			<jdoc:include type="modules" name="footer" style="none" />

			<div class="footer-menu">
				<ul class="nav-inline">
					<li class="item97">
					<a href="http://www.joomla.org"><span>Home</span></a>
					</li>
					<li class="item110"><a href="http://www.joomla.org/about-joomla.html"><span>About</span></a>
					</li>
					<li class="item98"><a href="http://community.joomla.org"><span>Community</span></a></li><li class="item99"><a href="http://forum.joomla.org"><span>Forum</span></a>
					</li>
					<li class="item100"><a href="http://extensions.joomla.org"><span>Extensions</span></a></li><li class="item206"><a href="http://resources.joomla.org"><span>Resources</span></a>
					</li>
					<li class="item102"><a href="http://docs.joomla.org"><span>Docs</span></a></li><li class="item101"><a href="http://developer.joomla.org"><span>Developer</span></a>
					</li>
					<li class="item103"><a href="http://shop.joomla.org"><span>Shop</span></a>
					</li>
				</ul>

				<ul class="nav-inline">
					<li><a href="http://www.joomla.org/accessibility-statement.html">Accessibility Statement</a></li>
					<li><a href="http://www.joomla.org/privacy-policy.html">Privacy Policy</a></li>
					<li><a href="<?php echo $this->baseurl ?>/login">Log in</a></li>
					<li><a href="http://www.rochenhost.com/joomla-hosting" target="_blank">Joomla Hosting by Rochen Ltd.</a></li>
				</ul>

				<p>&copy; 2005 - <?php echo date('Y');?> <a href="http://www.opensourcematters.org">Open Source Matters, Inc.</a> All rights reserved.</p>
			</div>
		</div>
	</div>
	<jdoc:include type="modules" name="debug" style="none" />
	<script>
		jQuery('.hasTooltip').tooltip()
	</script>
</body>
</html>
