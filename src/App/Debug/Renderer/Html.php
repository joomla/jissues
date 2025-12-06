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
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Kint\Kint;
use Kint\Renderer\RichRenderer;

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

        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        // Check if debug is only displayed for admin users
        if ($application->get('debug.admin')) {
            if (!$application->getUser()->isAdmin) {
                return '';
            }
        }

        if ($application->get('debug.database')) {
            $html[] = '<div id="dbgDatabase">';
            $html[] = '<h3>Database</h3>';

            $html[] = $this->renderDatabase();
            $html[] = '</div>';
        }

        if ($application->get('debug.system')) {
            $html[] = '<div id="dbgProfile">';
            $html[] = '<h3>Profile</h3>';
            $html[] = $application->getDebugger()->renderProfile();
            $html[] = '</div>';

            $oldReturn            = Kint::$return;
            Kint::$return         = true;
            RichRenderer::$folder = false;

            try {
                $html[] = '<div id="dbgUser">';
                $html[] = '<h3>User</h3>';
                $html[] = Kint::dump($application->getUser());
                $html[] = '</div>';

                if ($route = $application->get('_resolved_route')) {
                    $html[] = '<div id="dbgRoute">';
                    $html[] = '<h3>Route</h3>';
                    $html[] = Kint::dump($route);
                    $html[] = '</div>';
                }

                $html[] = '<div id="dbgProject">';
                $html[] = '<h3>Project</h3>';
                $html[] = Kint::dump($application->getProject());
                $html[] = '</div>';

                $html[] = '<div id="dbgRequest">';
                $html[] = '<h3>Request</h3>';
                $html[] = Kint::dump($application->input->getArray());
                $html[] = '</div>';
            } finally {
                Kint::$return = $oldReturn;
            }
        }

        if (!$html) {
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
        /** @var \JTracker\Application\Application $application */
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

        $html[] = '<a href="#top" class="brand hasTooltip" title="Go up">'
            . '&nbsp;<span aria-hidden="true" class="fab fa-joomla"></span></a>';

        $html[] = '<ul class="nav">';

        if ($application->get('debug.database')) {
            $count = \count($application->getDebugger()->getLog('db'));

            if ($count === 1) {
                $title = 'One database query';
            } else {
                $title = \sprintf('%d database queries', $count);
            }

            $html[] = '<li class="hasTooltip" title="' . $title . '">'
                . '<a href="#dbgDatabase"><span class="fab fa-database"></span> '
                . $this->getBadge($count)
                . '</a></li>';
        }

        if ($application->get('debug.system')) {
            $profile = $application->getDebugger()->getProfile();

            $html[] = '<li class="hasTooltip" title="Profile">'
                . '<a href="#dbgProfile"><span class="fas fa-bolt-lightning"></span> '
                . \sprintf('%s MB', $this->getBadge(number_format($profile->peak / 1000000, 2)))
                . ' '
                . \sprintf('%s ms', $this->getBadge(number_format($profile->time * 1000)))
                . '</a></li>';
        }

        if ($application->get('debug.system')) {
            $user    = $application->getUser();
            $project = $application->getProject();

            $title = $project ? $project->title : 'No Project';

            // Add build commit if available
            $buildHref = '#';

            if (file_exists(JPATH_ROOT . '/current_SHA')) {
                $build = trim(file_get_contents(JPATH_ROOT . '/current_SHA'));
                preg_match('/-g([0-9a-z]+)/', $build, $matches);
                $buildHref = $matches
                    ? 'https://github.com/joomla/jissues/commit/' . $matches[1]
                    : '#';
            } else {
                // Fall back to composer.json version
                $composer = json_decode(trim(file_get_contents(JPATH_ROOT . '/composer.json')));
                $build    = $composer->version;
            }

            $html[] = '<li class="hasTooltip" title="User">'
                . '<a href="#dbgUser"><span class="fas fa-user"></span> <span class="badge">'
                . ($user && $user->username ? $user->username : 'Guest')
                . '</span></a></li>';

            if ($application->get('_resolved_route')) {
                $html[] = '<li class="hasTooltip" title="Route">'
                    . '<a href="#dbgRoute"><span class="fas fa-route"></span> <span class="badge">Route</span></a></li>';
            }

            $html[] = '<li class="hasTooltip" title="Project">'
                . '<a href="#dbgProject"><span class="fas fa-cube"></span> <span class="badge">'
                . $title
                . '</span></a></li>';

            $html[] = '<li class="hasTooltip" title="Request variables">'
                . '<a href="#dbgRequest"><span class="fas fa-earth-europe"></span> <span class="badge">Request</span></a></li>';

            // Display the build to admins
            if ($application->getUser()->isAdmin) {
                $html[] = '<li class="hasTooltip" title="Build">'
                    . '<a href="' . $buildHref . '"><span class="fas fa-bullhorn"></span> <span class="badge">'
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

        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $debugger = $application->getDebugger();

        $dbLog = $debugger->getLog('db');

        if (!$dbLog) {
            return '';
        }

        $tableFormat = new TableFormat();
        $sqlFormat   = new SqlFormat();
        $dbDebugger  = new DatabaseDebugger($this->getContainer()->get('db'));

        if (\count($dbLog) === 1) {
            $debug[] = 'One database query';
        } else {
            $debug[] = \sprintf('%d database queries', \count($dbLog));
        }

        $prefix = $dbDebugger->getPrefix();

        foreach ($dbLog as $i => $entry) {
            $explain = $dbDebugger->getExplain($entry->sql);

            $debug[] = '<pre class="dbQuery">' . $sqlFormat->highlightQuery($entry->sql, $prefix) . '</pre>';

            if (isset($entry->times) && \is_array($entry->times)) {
                $debug[] = \sprintf('Query Time: %.3f ms', ($entry->times[1] - $entry->times[0]) * 1000) . '<br />';
            }

            // Tabs headers

            $debug[] = '<ul class="nav nav-tabs">';

            if ($explain) {
                $debug[] = '<li><a data-toggle="tab" href="#queryExplain-' . $i . '">Explain</a></li>';
            }

            if (isset($entry->trace) && \is_array($entry->trace)) {
                $debug[] = '<li><a data-toggle="tab" href="#queryTrace-' . $i . '">Trace</a></li>';
            }

            if (isset($entry->profile) && \is_array($entry->profile)) {
                $debug[] = '<li><a data-toggle="tab" href="#queryProfile-' . $i . '">Profile</a></li>';
            }

            $debug[] = '</ul>';

            // Tabs contents

            $debug[] = '<div class="tab-content">';

            if ($explain) {
                $debug[] = '<div id="queryExplain-' . $i . '" class="tab-pane">';

                $debug[] = $explain;
                $debug[] = '</div>';
            }

            if (isset($entry->trace) && \is_array($entry->trace)) {
                $debug[] = '<div id="queryTrace-' . $i . '" class="tab-pane">';
                $debug[] = $tableFormat->fromTrace($entry->trace);
                $debug[] = '</div>';
            }

            if (isset($entry->profile) && \is_array($entry->profile)) {
                $debug[] = '<div id="queryProfile-' . $i . '" class="tab-pane">';
                $debug[] = $tableFormat->fromArray($entry->profile);
                $debug[] = '</div>';
            }

            $debug[] = '</div>';
        }

        return implode("\n", $debug);
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

        foreach ($options as $opCount => $opClass) {
            if ($count >= $opCount) {
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
        if ($this->container) {
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
