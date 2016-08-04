<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Renderer;

use App\Debug\Database\DatabaseDebugger;
use App\Debug\Format\Html\SqlFormat;
use App\Debug\Format\Html\TableFormat;

use ElKuKu\G11n\G11n;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\Utilities\ArrayHelper;

use Kint;

/**
 * Class Html
 *
 * @since  1.0
 */
class Html implements ContainerAwareInterface
{
	/**
	 * @var Container
	 */
	private $container;

	/**
	 * Render it.
	 *
	 * @return string
	 */
	public function render()
	{
		$html = [];

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		// Check if debug is only displayed for admin users
		if ($application->get('debug.admin'))
		{
			if (!$application->getUser()->isAdmin)
			{
				return '';
			}
		}

		if ($application->get('debug.database'))
		{
			$html[] = '<div id="dbgDatabase">';
			$html[] = '<h3>' . g11n3t('Database') . '</h3>';

			$html[] = $this->renderDatabase();
			$html[] = '</div>';
		}

		if ($application->get('debug.system'))
		{
			$html[] = '<div id="dbgProfile">';
			$html[] = '<h3>' . g11n3t('Profile') . '</h3>';
			$html[] = $application->getDebugger()->renderProfile();
			$html[] = '</div>';

			$html[] = '<div id="dbgUser">';
			$html[] = '<h3>' . g11n3t('User') . '</h3>';
			$html[] = @Kint::dump($application->getUser());
			$html[] = '</div>';

			$html[] = '<div id="dbgProject">';
			$html[] = '<h3>' . g11n3t('Project') . '</h3>';
			$html[] = @Kint::dump($application->getProject());
			$html[] = '</div>';

			$html[] = '<div id="dbgRequest">';
			$html[] = '<h3>' . g11n3t('Request') . '</h3>';
			$html[] = @Kint::dump($_REQUEST);
			$html[] = '</div>';
		}

		if ($application->get('debug.language'))
		{
			$html[] = '<div id="dbgLanguageStrings">';
			$html[] = '<h3>' . g11n3t('Language Strings') . '</h3>';
			$html[] = $this->renderLanguageStrings();
			$html[] = '</div>';

			$html[] = '<div id="dbgLanguageFiles">';
			$html[] = '<h3>' . g11n3t('Language Files') . '</h3>';
			$html[] = $this->renderLanguageFiles();
			$html[] = '</div>';
		}

		if (!$html)
		{
			return '';
		}

		return implode("\n", $this->getNavigation()) . implode("\n", $html);
	}

	/**
	 * Get the navigation bar.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function getNavigation()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$html = [];

		// OK, here comes some very beautiful CSS !!
		// It's kinda "hidden" here, so evil template designers won't find it :P
		$html[] = '
		<style>
			div#debugBar { background-color: #eee; }
			div#debugBar a:hover { background-color: #ddd; }
			div#debugBar a:active { background-color: #ccc; }
			pre.dbQuery { background-color: #333; color: white; font-weight: bold; }
			span.dbgTable { color: yellow; }
			span.dbgCommand { color: lime; }
			span.dbgOperator { color: red; }
			div:target { border: 2px dashed orange; padding: 5px; transition:all 0.5s ease;}
			body { margin-bottom: 50px; }
		</style>
		';

		$html[] = '<div class="navbar navbar-fixed-bottom" id="debugBar">';

		$html[] = '<a href="#top" class="brand hasTooltip" title="' . g11n3t('Go up') . '">'
			. '&nbsp;<i class="icon icon-joomla"></i></a>';

		$html[] = '<ul class="nav">';

		if ($application->get('debug.database'))
		{
			$count = count($application->getDebugger()->getLog('db'));

			$html[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(g11n4t('One database query', '%d database queries', $count), $count) . '">'
				. '<a href="#dbgDatabase"><i class="icon icon-database"></i> '
				. $this->getBadge($count)
				. '</a></li>';
		}

		if ($application->get('debug.system'))
		{
			$profile = $application->getDebugger()->getProfile();

			$html[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('Profile') . '">'
				. '<a href="#dbgProfile"><i class="icon icon-lightning"></i> '
				. sprintf('%s MB', $this->getBadge(number_format($profile->peak / 1000000, 2)))
				. ' '
				. sprintf('%s ms', $this->getBadge(number_format($profile->time * 1000)))
				. '</a></li>';
		}

		if ($application->get('debug.language'))
		{
			$info = $application->getDebugger()->getLanguageStringsInfo();
			$count = count(G11n::getEvents());

			$html[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(
					g11n4t(
						'One untranslated string of %2$d', '%1$d untranslated strings of %2$d', $info->untranslateds
					), $info->untranslateds, $info->total
				) . '">'
				. '<a href="#dbgLanguageStrings"><i class="icon icon-question-sign"></i>  '
				. $this->getBadge($info->untranslateds, [1 => 'badge-warning']) . '/' . $this->getBadge($info->total)
				. '</a></li>';

			$html[] = '<li class="hasTooltip"'
				. ' title="' . sprintf(g11n4t('One language file loaded', '%d language files loaded', $count), $count) . '">'
				. '<a href="#dbgLanguageFiles"><i class="icon icon-file-word"></i> '
				. $this->getBadge($count)
				. '</a></li>';
		}

		if ($application->get('debug.system'))
		{
			$user    = $application->getUser();
			$project = $application->getProject();

			$title = $project ? $project->title : g11n3t('No Project');

			// Add build commit if available
			$buildHref = '#';

			if (file_exists(JPATH_ROOT . '/current_SHA'))
			{
				$build = trim(file_get_contents(JPATH_ROOT . '/current_SHA'));
				preg_match('/-g([0-9a-z]+)/', $build, $matches);
				$buildHref = $matches
					? 'https://github.com/joomla/jissues/commit/' . $matches[1]
					: '#';
			}
			// Fall back to composer.json version
			else
			{
				$composer = json_decode(trim(file_get_contents(JPATH_ROOT . '/composer.json')));
				$build    = $composer->version;
			}

			$html[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('User') . '">'
				. '<a href="#dbgUser"><i class="icon icon-user"></i> <span class="badge">'
				. ($user && $user->username ? $user->username : g11n3t('Guest'))
				. '</span></a></li>';

			$html[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('Project') . '">'
				. '<a href="#dbgProject"><i class="icon icon-cube"></i> <span class="badge">'
				. $title
				. '</span></a></li>';

			$html[] = '<li class="hasTooltip"'
				. ' title="' . g11n3t('Request variables') . '">'
				. '<a href="#dbgRequest"><i class="icon icon-earth"></i> <span class="badge">'
				. g11n3t('Request')
				. '</span></a></li>';

			// Display the build to admins
			if ($application->getUser()->isAdmin)
			{
				$html[] = '<li class="hasTooltip"'
					. ' title="' . g11n3t('Build') . '">'
					. '<a href="' . $buildHref . '"><i class="icon icon-broadcast"></i> <span class="badge">'
					. $build
					. '</span></a></li>';
			}
		}

		$html[] = '</ul>';
		$html[] = '</div>';

		return $html;
	}

	/**
	 * Render database information.
	 *
	 * @return  string  HTML markup for database debug
	 *
	 * @since   1.0
	 */
	private function renderDatabase()
	{
		$debug = [];

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$debugger = $application->getDebugger();

		$dbLog = $debugger->getLog('db');

		if (!$dbLog)
		{
			return '';
		}

		$tableFormat = new TableFormat;
		$sqlFormat   = new SqlFormat;
		$dbDebugger  = new DatabaseDebugger($this->getContainer()->get('db'));

		$debug[] = sprintf(g11n4t('One database query', '%d database queries', count($dbLog)), count($dbLog));

		$prefix = $dbDebugger->getPrefix();

		foreach ($dbLog as $i => $entry)
		{
			$explain = $dbDebugger->getExplain($entry->sql);

			$debug[] = '<pre class="dbQuery">' . $sqlFormat->highlightQuery($entry->sql, $prefix) . '</pre>';

			if (isset($entry->times) && is_array($entry->times))
			{
				$debug[] = sprintf('Query Time: %.3f ms', ($entry->times[1] - $entry->times[0]) * 1000) . '<br />';
			}

			// Tabs headers

			$debug[] = '<ul class="nav nav-tabs">';

			if ($explain)
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryExplain-' . $i . '">Explain</a></li>';
			}

			if (isset($entry->trace) && is_array($entry->trace))
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryTrace-' . $i . '">Trace</a></li>';
			}

			if (isset($entry->profile) && is_array($entry->profile))
			{
				$debug[] = '<li><a data-toggle="tab" href="#queryProfile-' . $i . '">Profile</a></li>';
			}

			$debug[] = '</ul>';

			// Tabs contents

			$debug[] = '<div class="tab-content">';

			if ($explain)
			{
				$debug[] = '<div id="queryExplain-' . $i . '" class="tab-pane">';

				$debug[] = $explain;
				$debug[] = '</div>';
			}

			if (isset($entry->trace) && is_array($entry->trace))
			{
				$debug[] = '<div id="queryTrace-' . $i . '" class="tab-pane">';
				$debug[] = $tableFormat->fromTrace($entry->trace);
				$debug[] = '</div>';
			}

			if (isset($entry->profile) && is_array($entry->profile))
			{
				$debug[] = '<div id="queryProfile-' . $i . '" class="tab-pane">';
				$debug[] = $tableFormat->fromArray($entry->profile);
				$debug[] = '</div>';
			}

			$debug[] = '</div>';
		}

		return implode("\n", $debug);
	}

	/**
	 * Render language debug information.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderLanguageFiles()
	{
		$items = [];
		$tableFormat = new TableFormat;

		foreach (G11n::getEvents() as $e)
		{
			$items[] = ArrayHelper::fromObject($e);
		}

		$pluralInfo = sprintf(
			g11n3t(
				'Plural forms: <code>%1$d</code><br />Plural function: <code>%2$s</code>'),
			G11n::get('pluralForms'), G11n::get('pluralFunctionRaw')
		);

		return $tableFormat->fromArray($items) . $pluralInfo;
	}

	/**
	 * Prints out translated and untranslated strings.
	 *
	 * @return  string  HTML markup for language debug
	 *
	 * @since   1.0
	 */
	protected function renderLanguageStrings()
	{
		$html = [];

		$items = G11n::get('processedItems');

		$html[] = '<table class="table table-hover table-condensed">';
		$html[] = '<tr>';
		$html[] = '<th>' . g11n3t('String') . '</th><th>' . g11n3t('File (line)') . '</th><th></th>';
		$html[] = '</tr>';

		$tableFormat = new TableFormat;

		$i = 0;

		foreach ($items as $string => $item)
		{
			$color =('-' == $item->status)
				? '#ffb2b2;'
				: '#e5ff99;';

			$html[] = '<tr>';
			$html[] = '<td style="border-left: 7px solid ' . $color . '">' . htmlentities($string) . '</td>';
			$html[] = '<td>' . str_replace(JPATH_ROOT, 'ROOT', $item->file) . ' (' . $item->line . ')</td>';
			$html[] = '<td><span class="btn btn-mini" onclick="$(\'#langStringTrace' . $i . '\').slideToggle();">Trace</span></td>';
			$html[] = '</tr>';

			$html[] = '<tr><td colspan="4" id="langStringTrace' . $i . '" style="display: none;">'
				. $tableFormat->fromTrace($item->trace)
				. '</td></tr>';

			$i ++;
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}

	/**
	 * Create a bootstrap HTML badge.
	 *
	 * an array with optional css class can be supplied. If the $count value exceeds the option value this class will be used.
	 * E.g.: [5 => 'warning', 15 => 'danger']
	 *
	 * @param   integer  $count    The number to display inside the badge.
	 * @param   array    $options  An indexed array of values and CSS classes.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	private function getBadge($count, array $options = [])
	{
		$class = '';

		foreach ($options as $opCount => $opClass)
		{
			if ($count >= $opCount)
			{
				$class = ' ' . $opClass;
			}
		}

		return '<span class="badge' . $class . '">' . $count . '</span>';
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @since   1.0
	 *
	 * @throws  \UnexpectedValueException May be thrown if the container has not been set.
	 */
	public function getContainer()
	{
		if ($this->container)
		{
			return $this->container;
		}

		throw new \UnexpectedValueException('Container not set in ' . __CLASS__);
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}
}
