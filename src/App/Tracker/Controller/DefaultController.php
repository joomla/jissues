<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use Joomla\Input\Input;
use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Tracker component.
 *
 * @since  1.0
 */
class DefaultController extends AbstractTrackerController
{
	/**
	 * Constructor
	 *
	 * @param   Container            $container  The DI container.
	 * @param   Input                $input      The input object.
	 * @param   AbstractApplication  $app        The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container, Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($container, $input, $app);

		// Set the default view
		$this->defaultView = 'issues';
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		if ($application->getProject()->project_id)
		{
			$application->getUser()->authorize('view');
		}

		parent::execute();
	}
}
