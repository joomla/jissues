<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Table;

use Joomla\Database\DatabaseDriver;

use Joomla\Factory;
use Joomla\Filter\OutputFilter;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__articles table
 *
 * @property   integer  $article_id    PK
 * @property   string   $title         The article title.
 * @property   string   $alias         The article alias.
 * @property   string   $text          The article text.
 * @property   string   $text_md       The raw article text.
 * @property   string   $created_date  The created date.
 *
 * @since  1.0
 */
class ArticlesTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__articles', 'article_id', $db);
	}

	/**
	 * Load an article by alias.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @since  1.0
	 * @return mixed
	 */
	public function loadByAlias($alias)
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName($this->tableName))
				->select(array('title', 'text'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
		)->loadObject();
	}

	/**
	 * Overloaded check function.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function check()
	{
		$errors = array();

		if (trim($this->alias) == '')
		{
			if (trim($this->title))
			{
				$this->alias = trim($this->title);
			}
			else
			{
				$errors[] = 'An alias or a title is required.';
			}
		}

		if (trim($this->text_md) == '')
		{
			$errors[] = 'Some text is required.';
		}

		$this->alias = OutputFilter::stringURLSafe($this->alias);

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}

	/**
	 * Method to store a row in the database from the AbstractDatabaseTable instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * AbstractDatabaseTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function store($updateNulls = false)
	{
		if (!$this->created_date)
		{
			// New item
			if (!$this->created_date)
			{
				$date               = new \DateTime;
				$this->created_date = $date->format('Y-m-d H:i:s');
			}
		}

		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		// Render markdown
		$this->text = $application->getGitHub()
			->markdown->render($this->text_md);

		return parent::store($updateNulls);
	}
}
