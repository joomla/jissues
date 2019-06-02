<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\View\Page;

use App\Text\Model\PageModel;

use Joomla\Router\Exception\RouteNotFoundException;
use JTracker\View\AbstractTrackerHtmlView;

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
	 * @var    PageModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * The page alias.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $alias = '';

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RouteNotFoundException
	 */
	public function render()
	{
		try
		{
			$item = $this->model->getItem($this->getAlias());
		}
		catch (\RuntimeException $e)
		{
			throw new RouteNotFoundException(sprintf('Unable to handle request for route `page/%s`.', $this->getAlias()), 404, $e);
		}

		$this->addData('page', $item->getIterator());

		return parent::render();
	}

	/**
	 * Get the page alias.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getAlias()
	{
		if ('' == $this->alias)
		{
			throw new \RuntimeException('Alias not set.');
		}

		return $this->alias;
	}

	/**
	 * Set the page alias.
	 *
	 * @param   string  $alias  The page alias.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}
}
