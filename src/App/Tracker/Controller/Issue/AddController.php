<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\View\Issue\IssueHtmlView;

use Joomla\Input\Input;

use JTracker\Application;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class AddController extends AbstractTrackerController
{
	/**
	 * @var  IssueHtmlView
	 */
	protected $view = null;

	/**
	 * Constructor
	 *
	 * @param   Input        $input  The input object.
	 * @param   Application  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, Application $app = null)
	{
		parent::__construct($input, $app);

		$app->getUser()->authorize('create');

		// Set the default view
		$input->set('view', 'issue');
		$input->set('layout', 'add');
	}

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->view->setProject($this->container->get('app')->getProject());
	}
}
