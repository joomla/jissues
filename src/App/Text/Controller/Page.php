<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use App\Text\View\Page\PageHtmlView;

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
	 * @var    PageHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @throws \JTracker\Router\Exception\RoutingException
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		$item = $this->getContainer()->get('EntityManager')
			->getRepository('App\Text\Entity\Article')
			->findOneBy(['alias' => $this->getContainer()->get('app')->input->getCmd('alias')]);

		if (!$item)
		{
			throw new RoutingException(g11n3t('This page does not exist.'));
		}

		$this->view->setItem($item);

		return $this;
	}
}
