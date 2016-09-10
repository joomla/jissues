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
 * Table interface class for the #__releases table
 *
 * @property   integer  $id            PK
 * @property   integer  $release_id    Release ID from GitHub
 * @property   integer  $milestone_id  Optional milestone to associate with this release
 * @property   string   $name          Release Name
 * @property   string   $tag_name      Name of the Git tag for this release
 * @property   string   $created_at    Date the release was created
 * @property   string   $notes         The HTML formatted release notes
 * @property   string   $notes_raw     The raw release notes (markdown)
 *
 * @since  1.0
 */
class ReleasesTable extends AbstractDatabaseTable
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
		parent::__construct('#__releases', 'id', $database);
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
		$errors = [];

		if (trim($this->name) == '')
		{
			$errors[] = g11n3t('A name is required for the release.');
		}

		if (strlen($this->name) > 50)
		{
			$errors[] = g11n3t('The length of the name can not exceed 50 characters.');
		}

		if (trim($this->tag_name) == '')
		{
			$errors[] = g11n3t('A tag name is required for the release.');
		}

		if (trim($this->created_at) == '')
		{
			$errors[] = g11n3t('The time this release was created is required.');
		}

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}
}
