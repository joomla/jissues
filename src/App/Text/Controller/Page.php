<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use JTracker\Controller\AbstractTrackerController;
use JTracker\Router\Exception\RoutingException;

/**
 * Controller class for the Text component
 *
 * @since  1.0
 */
class Page extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    \App\Text\View\Page\PageHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Model object
	 *
	 * @var    \App\Text\Model\PageModel
	 * @since  1.0
	 */
	protected $model = null;

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @throws RoutingException
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$item = $this->model->findOneBy(['alias' => $application->input->getCmd('alias')]);

		if (!$item)
		{
			throw new RoutingException(g11n3t('This page does not exist.'));
		}

		$this->view->setItem($item);

		return parent::execute();
	}
}
