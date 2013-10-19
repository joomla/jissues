<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\View\Page;

use App\Text\Model\PageModel;

use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

/**
 * Page view class
 *
 * @since  1.0
 */
class PageHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     PageModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 */
	public function render()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$alias = $application->input->getCmd('alias');

		try
		{
			$item = $this->model->getItem($alias);
		}
		catch (\RuntimeException $e)
		{
			throw new RoutingException($alias);
		}

		$this->renderer->set('page', $item->getIterator());

		return parent::render();
	}
}
