<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Documentor\Controller;

use App\Documentor\View\Documentation\DocumentationHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the developer documentation.
 *
 * @since  1.0
 */
class Documentation extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    DocumentationHtmlView
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

		/* @type $input \Joomla\Input\Input */
		$input = $this->getContainer()->get('app')->input;

		$path = $input->getPath('path');
		$page = $input->getCmd('page');

		if ($page)
		{
			$fullPath = 'page=' . $page . ($path ? '&path=' . $path : '');

			$this->view->setFullPath($fullPath);
		}
	}
}
