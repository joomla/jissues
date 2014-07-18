<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "issues_voting" database table.
 *
 * @Entity
 * @Table(
 *    name="#__issues_voting",
 *    indexes={
 * @Index(name="issues_voting_fk_issue_id", columns={"issue_number"}),
 * @Index(name="issues_voting_fk_user_id", columns={"user_id"})
 *    }
 * )
 *
 * @since  1.0
 */
class IssuesVotingTable extends AbstractDatabaseTable
{
	/**
	 * id
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
	 * Foreign key to #__issues.id
	 *
	 * @Column(name="issue_number", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $issueNumber;

	/**
	 * Foreign key to #__users.id
	 *
	 * @Column(name="user_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $userId;

	/**
	 * Flag indicating whether the user has experienced the issue
	 *
	 * @Column(name="experienced", type="smallint", length=2, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $experienced;

	/**
	 * User score for importance of issue
	 *
	 * @Column(name="score", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $score;

	/**
	 * Get:  id
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
	 * Set:  id
	 *
	 * @param   integer  $id  id
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
	 * Get:  Foreign key to #__issues.id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getIssueNumber()
	{
		return $this->issueNumber;
	}

	/**
	 * Set:  Foreign key to #__issues.id
	 *
	 * @param   integer  $issueNumber  Foreign key to #__issues.id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setIssueNumber($issueNumber)
	{
		$this->issueNumber = $issueNumber;

		return $this;
	}

	/**
	 * Get:  Foreign key to #__users.id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Set:  Foreign key to #__users.id
	 *
	 * @param   integer  $userId  Foreign key to #__users.id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * Get:  Flag indicating whether the user has experienced the issue
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getExperienced()
	{
		return $this->experienced;
	}

	/**
	 * Set:  Flag indicating whether the user has experienced the issue
	 *
	 * @param   integer  $experienced  Flag indicating whether the user has experienced the issue
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setExperienced($experienced)
	{
		$this->experienced = $experienced;

		return $this;
	}

	/**
	 * Get:  User score for importance of issue
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getScore()
	{
		return $this->score;
	}

	/**
	 * Set:  User score for importance of issue
	 *
	 * @param   integer  $score  User score for importance of issue
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setScore($score)
	{
		$this->score = $score;

		return $this;
	}

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__issues_voting', 'id', $database);
	}
}
