<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\View\Issue\IssueHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class Add extends AbstractTrackerController
{
	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issue';

	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'add';

	/**
	 * View object
	 *
	 * @var    IssueHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->getContainer()->get('app')->getUser()->authorize('create');

		$this->view->setProject($this->getContainer()->get('app')->getProject());
	}
}
