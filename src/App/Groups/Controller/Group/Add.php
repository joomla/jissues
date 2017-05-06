<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Controller\Group;

use App\Groups\View\Group\GroupHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add a group.
 *
 * @since  1.0
 */
class Add extends AbstractTrackerController
{
	/**
	 * The default view for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'group';

	/**
	 * The default layout for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * View object
	 *
	 * @var    GroupHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->getContainer()->get('app')->getUser()->authorize('manage');

		$this->view->setProject($this->getContainer()->get('app')->getProject());

		return $this;
	}
}
