<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerDoctrineModel;
use JTracker\Model\AbstractTrackerListModel;

/**
 * Users model class for the Users component.
 *
 * @since  1.0
 */
class UsersModel extends AbstractTrackerDoctrineModel
{
	/**
	 * The name of the entity.
	 *
	 * @var string
	 *
	 * @since  1.0
	 */
	protected $entityName = 'User';
}
