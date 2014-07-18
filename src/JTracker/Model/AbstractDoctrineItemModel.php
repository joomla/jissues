<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

/**
 * Abstract base model for the tracker application.
 *
 * NOTE: This class extends the AbstractModel class to be "compatible" with JView - 2BDeprecated...
 *
 * @since  1.0
 */
abstract class AbstractDoctrineItemModel extends AbstractDoctrineModel
{
	/**
	 * Get a single item.
	 *
	 * @param   integer  $id  The ID.
	 *
	 * @return object
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		return $this->getEntityManager()
		->find($this->getEntityClass(), $id);
	}

	/**
	 * Find a single item by condition(s).
	 *
	 * @param   array  $conditions  Indexed array with conditions.
	 *
	 * @return null|object
	 *
	 * @since   1.0
	 */
	public function findOneBy(array $conditions)
	{
		return $this->getEntityManager()
					->getRepository($this->getEntityClass())
					->findOneBy($conditions);
	}
}
