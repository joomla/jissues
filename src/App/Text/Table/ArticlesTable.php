<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Table;

use Joomla\DI\Container;
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
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		parent::__construct($container, '#__articles', 'article_id');
	}

	/**
	 * Load an article by alias.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
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

		if (trim($this->alias) == '')
		{
			if (trim($this->title))
			{
				$this->alias = trim($this->title);
			}
			else
			{
				$errors[] = g11n3t('An alias or a title is required.');
			}
		}

		if (trim($this->text_md) == '')
		{
			$errors[] = g11n3t('Some text is required.');
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
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * AbstractDatabaseTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function store($updateNulls = false)
	{
		if (!$this->created_date)
		{
			// New item - set an (arbitrary) created date..
			$this->created_date = with(new \DateTime)->format('Y-m-d H:i:s');
		}

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->container->get('gitHub');

		// Render markdown
		$this->text = $gitHub->markdown
			->render($this->text_md);

		return parent::store($updateNulls);
	}
}
