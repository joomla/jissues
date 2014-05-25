<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use Doctrine\ORM\Tools\Pagination\Paginator;

use JTracker\Pagination\TrackerPagination;

/**
 * Abstract HTML view class for the Tracker application
 *
 * @since  1.0
 */
abstract class AbstractDoctrineHtmlListView extends AbstractTrackerHtmlView
{
	/**
	 * A Doctrine Paginator object.
	 *
	 * @var  Paginator
	 *
	 * @since  1.0
	 */
	private $paginator = null;

	/**
	 * The HTML pagination object.
	 *
	 * @var TrackerPagination
	 *
	 * @since  1.0
	 */
	private $pagination = null;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('items', $this->getPaginator());
		$this->renderer->set('pagination', $this->getPagination());

		return parent::render();
	}

	/**
	 * Set the paginator object.
	 *
	 * @param   Paginator  $paginator  The paginator object.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setPaginator(Paginator $paginator)
	{
		$this->paginator = $paginator;

		return $this;
	}

	/**
	 * Get the paginator object.
	 *
	 * @return Paginator
	 *
	 * @since   1.0
	 */
	public function getPaginator()
	{
		return $this->paginator;
	}

	/**
	 * Set the pagination.
	 *
	 * @param   TrackerPagination  $pagination  The pagination
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setPagination(TrackerPagination $pagination)
	{
		$this->pagination = $pagination;

		return $this;
	}

	/**
	 * Get a pagination object.
	 *
	 * @throws \UnexpectedValueException
	 * @return TrackerPagination
	 *
	 * @since   1.0
	 */
	public function getPagination()
	{
		if (is_null($this->pagination))
		{
			throw new \UnexpectedValueException('Pagination not set');
		}

		return $this->pagination;
	}
}
