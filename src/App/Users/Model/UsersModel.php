<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use JTracker\Model\AbstractDoctrineListModel;

/**
 * Users model class for the Users component.
 *
 * @since  1.0
 */
class UsersModel extends AbstractDoctrineListModel
{
	/**
	 * The name of the entity.
	 *
	 * @var string
	 *
	 * @since  1.0
	 */
	protected $entityName = 'User';

	/**
	 * Get the list query.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		return 'SELECT u FROM App\Users\Entity\User u';
	}
}
