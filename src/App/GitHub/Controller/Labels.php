<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * View object
	 *
	 * @var    LabelsHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
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
