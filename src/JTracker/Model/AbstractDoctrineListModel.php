<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Abstract base model for the tracker application.
 *
 * NOTE: This class extends the AbstractModel class to be "compatible" with JView - 2BDeprecated...
 *
 * @since  1.0
 */
abstract class AbstractDoctrineListModel extends AbstractDoctrineModel
{

	/**
	 * Get the list query.
	 *
	 * This method must be implemented in child classes.
	 *
	 * @throws \RuntimeException
	 * @return string
	 *
	 * @since   1.0
	 */
	abstract protected function getListQuery();

	/**
	 * Get a list of items.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		return $this->getEntityManager()
					->getRepository($this->getEntityClass())
					->findAll();
	}

	/**
	 * Get a list of items.
	 *
	 * @param   integer  $firstResult  The first result count.
	 * @param   integer  $maxResults   The max result count.
	 *
	 * @return Paginator
	 *
	 * @since   1.0
	 */
	public function getPaginator($firstResult, $maxResults)
	{
		return new Paginator(
			$this->getEntityManager()
				->createQuery($this->getListQuery())
				->setFirstResult($firstResult)
				->setMaxResults($maxResults)
		);
	}
}
