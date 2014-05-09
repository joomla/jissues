<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Entity;

use Joomla\Filter\OutputFilter;

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
 * @HasLifecycleCallbacks
 *
 * @since  1.0
 */
class Article
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
	 * The article title
	 *
	 * @Column(name="title", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @Filter(type="string")
	 *
	 * @since  1.0
	 */
	private $title = '';

	/**
	 * The article alias.
	 *
	 * @Column(name="alias", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @Filter(type="string")
	 *
	 * @since  1.0
	 */
	private $alias = '';

	/**
	 * The article text.
	 *
	 * @Column(name="text", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @Filter(type="html")
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
	 * @Filter(type="html")
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
		$this->articleId = (int) $articleId;

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
	 * Method to perform sanity checks on the DatabaseTable instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @PrePersist
	 * @PreUpdate
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function check()
	{
		$errors = array();

		if (!$this->createdDate)
		{
			// New item - set an (arbitrary) created date..
			$this->createdDate = new \DateTime;
		}

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

		if (trim($this->text) == '')
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
}
