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
 * @Table(name="_issues_voting")
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
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $id;

	/**
	 * Foreign key to #__issues.id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $issue_number;

	/**
	 * Foreign key to #__users.id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $user_id;

	/**
	 * Flag indicating whether the user has experienced the issue
	 *
	 * @Column(type="smallint", length=2)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $experienced;

	/**
	 * User score for importance of issue
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $score;

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