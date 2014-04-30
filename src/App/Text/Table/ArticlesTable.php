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
 * @Table(
 *    name="#__articles",
 *    indexes={
 * @Index(name="alias", columns={"alias"})}
 * )
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
	 * @Column(name="article_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $articleId;

	/**
	 * The article path
	 *
	 * @Column(name="path", type="string", length=500, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $path;

	/**
	 * The article title
	 *
	 * @Column(name="title", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $title;

	/**
	 * The article alias.
	 *
	 * @Column(name="alias", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $alias;

	/**
	 * The article text.
	 *
	 * @Column(name="text", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $text;

	/**
	 * The raw article text.
	 *
	 * @Column(name="text_md", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $textMd;

	/**
	 * The created date.
	 *
	 * @Column(name="created_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $createdDate;

	/**
	 * If the text is present as a file (for different handling)
	 *
	 * @Column(name="is_file", type="integer", length=1, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $isFile;

	/**
	 * Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $gitHub = null;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getArticleId()
	{
		return $this->articleId;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $articleId  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setArticleId($articleId)
	{
		$this->articleId = $articleId;

		return $this;
	}

	/**
	 * Get:  The article path
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set:  The article path
	 *
	 * @param   string  $path  The article path
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setPath($path)
	{
		$this->path = $path;

		return $this;
	}

	/**
	 * Get:  The article title
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set:  The article title
	 *
	 * @param   string  $title  The article title
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Get:  The article alias.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Set:  The article alias.
	 *
	 * @param   string  $alias  The article alias.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}

	/**
	 * Get:  The article text.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * Set:  The article text.
	 *
	 * @param   string  $text  The article text.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setText($text)
	{
		$this->text = $text;

		return $this;
	}

	/**
	 * Get:  The raw article text.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getTextMd()
	{
		return $this->textMd;
	}

	/**
	 * Set:  The raw article text.
	 *
	 * @param   string  $textMd  The raw article text.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setTextMd($textMd)
	{
		$this->textMd = $textMd;

		return $this;
	}

	/**
	 * Get:  The created date.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getCreatedDate()
	{
		return $this->createdDate;
	}

	/**
	 * Set:  The created date.
	 *
	 * @param   string  $createdDate  The created date.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCreatedDate($createdDate)
	{
		$this->createdDate = $createdDate;

		return $this;
	}

	/**
	 * Get:  If the text is present as a file (for different handling)
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getIsFile()
	{
		return $this->isFile;
	}

	/**
	 * Set:  If the text is present as a file (for different handling)
	 *
	 * @param   integer  $isFile  If the text is present as a file (for different handling)
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setIsFile($isFile)
	{
		$this->isFile = $isFile;

		return $this;
	}

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
