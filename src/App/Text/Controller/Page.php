<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller;

use App\Text\View\Page\PageHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the Text component
 *
 * @since  1.0
 */
class Page extends AbstractTrackerController
{
	/**
	 * @var PageHtmlView
	 */
	protected $view = null;

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

		$this->view->setAlias($this->container->get('app')->input->getCmd('alias'));
	}
}
