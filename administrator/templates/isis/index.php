<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.0
 */

defined('_JEXEC') or die;

$app   = JFactory::getApplication();
$doc   = JFactory::getDocument();
$lang  = JFactory::getLanguage();
$this->language = $doc->language;
$this->direction = $doc->direction;
$input = $app->input;
$user  = JFactory::getUser();

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

// Add Stylesheets
$doc->addStyleSheet('templates/' . $this->template . '/css/template.css');

// Load optional rtl bootstrap css and bootstrap bugfixes
JHtmlBootstrap::loadCss($includeMaincss = false, $this->direction);

// Load specific language related CSS
$file = 'language/' . $lang->getTag() . '/' . $lang->getTag() . '.css';
if (is_file($file))
{
	$doc->addStyleSheet($file);
}

// Detecting Active Variables
$option   = $input->get('option', '');
$view     = $input->get('view', '');
$layout   = $input->get('layout', '');
$task     = $input->get('task', '');
$itemid   = $input->get('Itemid', '');
$sitename = $app->getCfg('sitename');

$cpanel = ($option === 'com_cpanel');

$showSubmenu = false;
$this->submenumodules = JModuleHelper::getModules('submenu');
foreach ($this->submenumodules as $submenumodule)
{
	$output = JModuleHelper::renderModule($submenumodule);
	if (strlen($output))
	{
		$showSubmenu = true;
		break;
	}
}

// Logo file
if ($this->params->get('logoFile'))
{
	$logo = JURI::root() . $this->params->get('logoFile');
}
else
{
	$logo = $this->baseurl . "/templates/" . $this->template . "/images/logo.png";
}

// Template Parameters
$displayHeader = $this->params->get('displayHeader', '1');
$statusFixed = $this->params->get('statusFixed', '1');
$stickyToolbar = $this->params->get('stickyToolbar', '1');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<jdoc:include type="head" />
	<?php
	// Template color
	if ($this->params->get('templateColor'))
	{
	?>
	<style type="text/css">
		.navbar-inner, .navbar-inverse .navbar-inner, .nav-list > .active > a, .nav-list > .active > a:hover, .dropdown-menu li > a:hover, .dropdown-menu .active > a, .dropdown-menu .active > a:hover, .navbar-inverse .nav li.dropdown.open > .dropdown-toggle, .navbar-inverse .nav li.dropdown.active > .dropdown-toggle, .navbar-inverse .nav li.dropdown.open.active > .dropdown-toggle, #status.status-top
		{
			background: <?php echo $this->params->get('templateColor');?>;
		}
		.navbar-inner, .navbar-inverse .nav li.dropdown.open > .dropdown-toggle, .navbar-inverse .nav li.dropdown.active > .dropdown-toggle, .navbar-inverse .nav li.dropdown.open.active > .dropdown-toggle{
			-moz-box-shadow: 0 1px 3px rgba(0, 0, 0, .25), inset 0 -1px 0 rgba(0, 0, 0, .1), inset 0 30px 10px rgba(0, 0, 0, .2);
			-webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, .25), inset 0 -1px 0 rgba(0, 0, 0, .1), inset 0 30px 10px rgba(0, 0, 0, .2);
			box-shadow: 0 1px 3px rgba(0, 0, 0, .25), inset 0 -1px 0 rgba(0, 0, 0, .1), inset 0 30px 10px rgba(0, 0, 0, .2);
		}
	</style>
	<?php
	}
	?>
	<?php
	// Template header color
	if ($this->params->get('headerColor'))
	{
	?>
	<style type="text/css">
		.header
		{
			background: <?php echo $this->params->get('headerColor');?>;
		}
	</style>
	<?php
	}
	?>
	<!--[if lt IE 9]>
		<script src="../media/jui/js/html5.js"></script>
	<![endif]-->
</head>

<body class="admin <?php echo $option . " view-" . $view . " layout-" . $layout . " task-" . $task . " itemid-" . $itemid . " ";?>" <?php if ($stickyToolbar): ?>data-spy="scroll" data-target=".subhead" data-offset="87"<?php endif;?>>
	<!-- Top Navigation -->
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<?php if ($this->params->get('admin_menus') != '0') : ?>
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
				<?php endif; ?>
				<a class="brand" href="<?php echo JURI::root(); ?>" title="<?php echo JText::_('JGLOBAL_PREVIEW');?> <?php echo $sitename; ?>" target="_blank"><?php echo JHtml::_('string.truncate', $sitename, 14, false, false);?> <i class="icon-out-2 small"></i></a>
				<?php if ($this->params->get('admin_menus') != '0') : ?>
				<div class="nav-collapse">
				<?php else : ?>
				<div>
				<?php endif; ?>
					<jdoc:include type="modules" name="menu" style="none" />
					<ul class="<?php if ($this->direction == 'rtl') : ?>nav<?php else : ?>nav pull-right<?php endif; ?>">
						<li class="dropdown"> <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $user->name; ?> <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li class=""><a href="index.php?option=com_admin&task=profile.edit&id=<?php echo $user->id;?>"><?php echo JText::_('TPL_ISIS_EDIT_ACCOUNT');?></a></li>
								<li class="divider"></li>
								<li class=""><a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&'. JSession::getFormToken() .'=1');?>"><?php echo JText::_('TPL_ISIS_LOGOUT');?></a></li>
							</ul>
						</li>
					</ul>
				</div>
				<!--/.nav-collapse -->
			</div>
		</div>
	</nav>
	<!-- Header -->
	<?php
	if ($displayHeader):
	?>
	<header class="header">
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span2 container-logo">
					<a class="logo" href="<?php echo $this->baseurl; ?>"><img src="<?php echo $logo;?>" alt="<?php echo $sitename; ?>" /></a>
				</div>
				<div class="span10">
					<h1 class="page-title"><?php echo JHtml::_('string.truncate', $app->JComponentTitle, 0, false, false);?></h1>
				</div>
			</div>
		</div>
	</header>
	<?php
	endif;
	?>
	<?php
	if ((!$statusFixed) && ($this->countModules('status'))):
	?>
	<!-- Begin Status Module -->
	<div id="status" class="navbar status-top hidden-phone">
		<div class="btn-toolbar">
			<jdoc:include type="modules" name="status" style="no" />
		</div>
		<div class="clearfix"></div>
	</div>
	<!-- End Status Module -->
	<?php
	endif;
	?>
	<?php
	if (!$cpanel):
	?>
	<!-- Subheader -->
	<a class="btn btn-subhead" data-toggle="collapse" data-target=".subhead-collapse"><?php echo JText::_('TPL_ISIS_TOOLBAR');?> <i class="icon-wrench"></i></a>
	<div class="subhead-collapse">
		<div class="subhead">
			<div class="container-fluid">
				<div id="container-collapse" class="container-collapse"></div>
				<div class="row-fluid">
					<div class="span12">
						<jdoc:include type="modules" name="toolbar" style="no" />
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	else:
	?>
	<div style="margin-bottom: 20px"></div>
	<?php
	endif;
	?>
	<!-- container-fluid -->
	<div class="container-fluid container-main">
		<section id="content">
			<!-- Begin Content -->
			<jdoc:include type="modules" name="top" style="xhtml" />
			<div class="row-fluid">
				<?php if ($showSubmenu) : ?>
					<div class="span2">
						<jdoc:include type="modules" name="submenu" style="none" />
					</div>
					<div class="span10">
				<?php else : ?>
					<div class="span12">
				<?php endif; ?>
						<jdoc:include type="message" />
						<?php
						// Show the page title here if the header is hidden
						if (!$displayHeader):
						?>
						<h1 class="content-title"><?php echo JHtml::_('string.truncate', $app->JComponentTitle, 0, false, false);?></h1>
						<?php
						endif;
						?>
						<jdoc:include type="component" />
					</div>
			</div>
			<jdoc:include type="modules" name="bottom" style="xhtml" />
			<!-- End Content -->
		</section>
		<hr />
		<?php if (!$this->countModules('status')): ?>
			<footer class="footer">
				<p>&copy; <?php echo $sitename; ?> <?php echo date('Y');?></p>
			</footer>
		<?php endif; ?>
	</div>
	<?php if (($statusFixed) && ($this->countModules('status'))): ?>
	<!-- Begin Status Module -->
	<div id="status" class="navbar navbar-fixed-bottom hidden-phone">
		<div class="btn-toolbar">
			<div class="btn-group pull-right">
				<p>&copy; <?php echo $sitename; ?> <?php echo date('Y');?></p>
			</div>
			<jdoc:include type="modules" name="status" style="no" />
		</div>
	</div>
	<!-- End Status Module -->
	<?php endif; ?>
	<jdoc:include type="modules" name="debug" style="none" />
	<script>
		(function($){
			$('*[rel=tooltip]').tooltip()

			<?php
			if ($stickyToolbar):
			?>
			// fix sub nav on scroll
			var $win = $(window)
			  , $nav = $('.subhead')
			  , navTop = $('.subhead').length && $('.subhead').offset().top - <?php if ($displayHeader || !$statusFixed): ?>40<?php else:?>20<?php endif;?>
			  , isFixed = 0

			processScroll()

			// hack sad times - holdover until rewrite for 2.1
			$nav.on('click', function () {
				if (!isFixed) setTimeout(function () {  $win.scrollTop($win.scrollTop() - 47) }, 10)
			})

			$win.on('scroll', processScroll)

			function processScroll() {
				var i, scrollTop = $win.scrollTop()
				if (scrollTop >= navTop && !isFixed) {
					isFixed = 1
					$nav.addClass('subhead-fixed')
				} else if (scrollTop <= navTop && isFixed) {
					isFixed = 0
					$nav.removeClass('subhead-fixed')
				}
			}
			<?php
			endif;
			?>

			// Turn radios into btn-group
		    $('.radio.btn-group label').addClass('btn');
		    $(".btn-group label:not(.active)").click(function() {
		        var label = $(this);
		        var input = $('#' + label.attr('for'));

		        if (!input.prop('checked')) {
		            label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
		            if(input.val()== '') {
		                    label.addClass('active btn-primary');
		             } else if(input.val()==0) {
		                    label.addClass('active btn-danger');
		             } else {
		            label.addClass('active btn-success');
		             }
		            input.prop('checked', true);
		        }
		    });
		    $(".btn-group input[checked=checked]").each(function() {
				if($(this).val()== '') {
		           $("label[for=" + $(this).attr('id') + "]").addClass('active btn-primary');
		        } else if($(this).val()==0) {
		           $("label[for=" + $(this).attr('id') + "]").addClass('active btn-danger');
		        } else {
		            $("label[for=" + $(this).attr('id') + "]").addClass('active btn-success');
		        }
		    });
		})(jQuery);
	</script>
</body>
</html>
