<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;
use App\Tracker\View\Issue\IssueHtmlView;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

use JTracker\Controller\AbstractTrackerController;

/**
 * Item controller class for the Tracker component.
 *
 * @since  1.0
 */
class ItemController extends AbstractTrackerController
{
	/**
	 * @var  IssueHtmlView
	 */
	protected $view = null;

	/**
	 * @var  IssueModel
	 */
	protected $model = null;

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
		$input->set('view', 'issue');
		$this->defaultView = 'issue';
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

		$this->view->setId($this->container->get('app')->input->getUint('id'));
		$this->view->setProject($this->container->get('app')->getProject());
		$this->model->setProject($this->container->get('app')->getProject());

		return $this;
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
		$this->container->get('app')->getUser()->authorize('view');

		parent::execute();
	}
}
