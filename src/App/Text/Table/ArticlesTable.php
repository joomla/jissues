<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Filter\OutputFilter;
use Joomla\Github\Github;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "articles" database table.
 *
 * @Entity
 * @Table(name="#__articles")
 *
 * @since  1.0
 */
class ArticlesTable extends AbstractDatabaseTable
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $article_id;

	/**
	 * The article path
	 *
	 * @Column(type="string", length=500)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $path;

	/**
	 * The article title
	 *
	 * @Column(type="string", length=250)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $title;

	/**
	 * The article alias.
	 *
	 * @Column(type="string", length=250)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $alias;

	/**
	 * The article text.
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $text;

	/**
	 * The raw article text.
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $text_md;

	/**
	 * The created date.
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $created_date;

	/**
	 * If the text is present as a file (for different handling)
	 *
	 * @Column(type="integer", length=1)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $is_file;

	/**
	 * Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $gitHub = null;

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

		$this->alias = OutputFilter::stringURLUnicodeSlug($this->alias);

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
		if (!$this->created_date || $this->created_date == $this->db->getNullDate())
		{
			// New item - set an (arbitrary) created date..
			$this->created_date = (new \DateTime)->format('Y-m-d H:i:s');
		}

		// Render markdown
		$this->text = $this->getGitHub()->markdown
			->render($this->text_md);

		return parent::store($updateNulls);
	}

	/**
	 * Get the GitHub object.
	 *
	 * @return  Github
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getGitHub()
	{
		if (is_null($this->gitHub))
		{
			throw new \UnexpectedValueException('GitHub object not set.');
		}

		return $this->gitHub;
	}

	/**
	 * Set the GitHub object.
	 *
	 * @param   Github  $gitHub  The GitHub object.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setGitHub(Github $gitHub)
	{
		$this->gitHub = $gitHub;

		return $this;
	}
}
