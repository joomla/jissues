<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Entity;

use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

use JTracker\Database\AbstractDatabaseTable;

/**
 * User entity.
 *
 * @Entity
 * @Table(name="#__users",
 *    indexes={
 * @Index(name="idx_name", columns={"name"}),
 * @Index(name="idx_block", columns={"block"}),
 * @Index(name="username", columns={"username"}),
 * @Index(name="email", columns={"email"})
 *    }
 * )
 *
 * @since  1.0
 */
class User
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
	protected $id;

	/**
	 * The users name
	 *
	 * @Column(type="string", length=255)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $name = '';

	/**
	 * The users username
	 *
	 * @Column(type="string", length=150)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $username = '';

	/**
	 * The users e-mail
	 *
	 * @Column(type="string", length=100)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $email = '';

	/**
	 * If the user is blocked
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	protected $block = '0';

	/**
	 * If the users recieves e-mail
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	protected $sendEmail = '0';

	/**
	 * The register date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $registerDate = '0000-00-00 00:00:00';

	/**
	 * The last visit date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $lastvisitDate = '0000-00-00 00:00:00';

	/**
	 * Parameters
	 *
	 * @Column(type="text")
	 *
	 * @Filter(type="html")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	protected $params;

	/**
	 * Group.
	 *
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="Accessgroups", inversedBy="user")
	 * @ORM\JoinTable(name="#__user_accessgroup_map",
	 *   joinColumns={
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
	 *   },
	 *   inverseJoinColumns={
	 * @ORM\JoinColumn(name="group_id", referencedColumnName="group_id")
	 *   }
	 * )
	 *
	 * @since  1.0
	 */
	private $group;

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
		$this->id = $id;

		return $this;
	}

	/**
	 * Get:  The users name
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set:  The users name
	 *
	 * @param   string  $name  The users name
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get:  The users username
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * Set:  The users username
	 *
	 * @param   string  $username  The users username
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * Get:  The users e-mail
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Set:  The users e-mail
	 *
	 * @param   string  $email  The users e-mail
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setEmail($email)
	{
		$this->email = $email;

		return $this;
	}

	/**
	 * Get:  If the user is blocked
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * Set:  If the user is blocked
	 *
	 * @param   integer  $block  If the user is blocked
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setBlock($block)
	{
		$this->block = $block;

		return $this;
	}

	/**
	 * Get:  If the users recieves e-mail
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getSendEmail()
	{
		return $this->sendEmail;
	}

	/**
	 * Set:  If the users recieves e-mail
	 *
	 * @param   integer  $sendEmail  If the users recieves e-mail
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setSendEmail($sendEmail)
	{
		$this->sendEmail = $sendEmail;

		return $this;
	}

	/**
	 * Get:  The register date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getRegisterDate()
	{
		return $this->registerDate;
	}

	/**
	 * Set:  The register date
	 *
	 * @param   string  $registerDate  The register date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setRegisterDate($registerDate)
	{
		$this->registerDate = $registerDate;

		return $this;
	}

	/**
	 * Get:  The last visit date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getLastvisitDate()
	{
		return $this->lastvisitDate;
	}

	/**
	 * Set:  The last visit date
	 *
	 * @param   string  $lastvisitDate  The last visit date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setLastvisitDate($lastvisitDate)
	{
		$this->lastvisitDate = $lastvisitDate;

		return $this;
	}

	/**
	 * Get:  Parameters
	 *
	 * @return   Registry
	 *
	 * @since  1.0
	 */
	public function getParams()
	{
		static $params;

		if (!$params)
		{
			$params = new Registry($this->params);
		}

		return $params;
	}

	/**
	 * Set:  Parameters
	 *
	 * @param   string  $params  Parameters
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setParams($params)
	{
		$this->params = $params;

		return $this;
	}
}
