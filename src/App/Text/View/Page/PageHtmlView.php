<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\View\Page;

use App\Text\Model\PageModel;
use Joomla\Factory;
use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Users view class for the Users component
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
	 * @since  1.0
	 * @throws \JTracker\Router\Exception\RoutingException
	 * @return string  The rendered view.
	 */
	public function render()
	{
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

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
