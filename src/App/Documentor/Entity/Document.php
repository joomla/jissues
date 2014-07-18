<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Documentor\Entity;

/**
 * Table interface class for the "documents" database table.
 *
 * @Entity
 * @Table(
 *    name="#__documents",
 *    indexes={
 * @Index(name="page", columns={"page"})}
 * )
 *
 * @HasLifecycleCallbacks
 *
 * @since  1.0
 */
class Document
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(name="id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $id;

	/**
	 * The article path
	 *
	 * @Column(name="path", type="string", length=500, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $path = '';

	/**
	 * The page name.
	 *
	 * @Column(name="page", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $page = '';

	/**
	 * The article text.
	 *
	 * @Column(name="text", type="text", nullable=false)
	 *
	 * @Filter(type="raw")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $text;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $id  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setId($id)
	{
		$this->id = (int) $id;

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
	 * Get:  The page title.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * Set:  The page title.
	 *
	 * @param   string  $page  The page title.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setPage($page)
	{
		$this->page = $page;

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

		if (trim($this->page) == '')
		{
			$errors[] = g11n3t('A page title is required.');
		}

		if (trim($this->text) == '')
		{
			$errors[] = g11n3t('Some text is required.');
		}

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}
}
