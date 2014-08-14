<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use JTracker\Database\AbstractDatabaseTable;
use Joomla\Database\DatabaseDriver;

/**
 * Table interface class for the #__issue_category_mapping table
 *
 * @property   integer   $id           PK
 * @property   integer   $issue_id     The issue's id, PK in issue
 * @property   integer   $category_id  Category's ID
 *
 * @since  1.0
 */
class IssueCategoryMappingTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__issue_category_map', 'id', $database);
	}

	/**
	 * Method to perform sanity checks on the AbstractDatabaseTable instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function check()
	{
		$errors = array();

		if (trim($this->issue_id) == '')
		{
			$errors[] = g11n3t('Issue id is needed');
		}

		if (trim($this->category_id) == '')
		{
			$errors[] = g11n3t('Category ID is needed.');
		}

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}
}
