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
 * Controller class to edit an item via the tracker component.
 *
 * @since  1.0
 */
class Edit extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issue';

	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

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
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('edit');

		$this->view->setId($this->container->get('app')->input->getUint('id'));
		$this->view->setProject($this->container->get('app')->getProject());

		$this->model->setProject($this->container->get('app')->getProject());

		return parent::execute();
	}
}
