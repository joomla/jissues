<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Category;

use App\Tracker\View\Category\CategoryHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 *  Controller class to add an item into the categories
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
	protected $defaultView = 'category';

	/**
	 * The default layout for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'add';

	/**
	 * View object
	 *
	 * @var    CategoryHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$application->getUser()->authorize('manage');
		$this->view->setProject($application->getProject());

		$item = new \stdClass;
		$this->view->setItem($item);

		return parent::execute();
	}
}
