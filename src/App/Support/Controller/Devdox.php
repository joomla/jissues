<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'devdox';

	/**
	 * @var DevdoxHtmlView
	 */
	protected $view = null;

	public function initialize()
	{
		parent::initialize();

		$this->view->setAlias($this->container->get('app')->input->getCmd('alias'));
	}
}
