<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller;

use App\GitHub\View\Hooks\HooksHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for managing webhooks
 *
 * @since  1.0
 */
class Hooks extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'hooks';

	/**
	 * @var  HooksHtmlView
	 */
	protected $view;

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('admin');

		parent::execute();
	}

	public function initialize()
	{
		parent::initialize();

		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}


}
