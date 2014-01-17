<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller;

use App\Support\View\Devdox\DevdoxHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the developer documentation.
 *
 * @since  1.0
 */
class Devdox extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    DevdoxHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->view->setAlias($this->container->get('app')->input->getCmd('alias'));
	}
}
