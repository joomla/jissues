<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\Controller\AbstractTrackerController;

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
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the default view
		$this->defaultView = 'issues';

		// Set the view based on the request
		$input = $this->getInput();

		$projectAlias = $input->get('project_alias');

		$projectModel = new ProjectModel;

		$project = $projectModel->getByAlias($projectAlias);

		if ($project)
		{
			$input->set('project_id', $project->project_id);
		}
		else
		{
			// No project... CRY :(
		}

		if ($input->getInt('id', 0) >= 1)
		{
			$input->set('view', 'issue');
			$this->defaultView = 'issue';
		}
	}
}
