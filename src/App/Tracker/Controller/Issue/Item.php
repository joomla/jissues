<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;
use App\Tracker\View\Issue\IssueHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Item controller class for the Tracker component.
 *
 * @since  1.0
 */
class Item extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issue';

	/**
	 * View object
	 *
	 * @var    IssueHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Model object
	 *
	 * @var    IssueModel
	 * @since  1.0
	 */
	protected $model = null;

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

		$this->container->get('app')->getUser()->authorize('view');

		$this->view->setId($this->container->get('app')->input->getUint('id'));
		$this->view->setProject($this->container->get('app')->getProject());

		$this->model->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
