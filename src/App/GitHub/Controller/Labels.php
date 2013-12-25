<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller;

use App\GitHub\View\Labels\LabelsHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for managing labels
 *
 * @since  1.0
 */
class Labels extends AbstractTrackerController
{
	/**
	 * @var  LabelsHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->container->get('app')->getUser()->authorize('admin');

		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
