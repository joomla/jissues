<?php
/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication\Exception;

use JTracker\Authentication\User;

/**
 * AuthenticationException
 *
 * @since  1.0
 */
class AuthenticationException extends \Exception
{
	/**
	 * The user object.
	 *
	 * @var    User
	 * @since  1.0
	 */
	protected $user;

	/**
	 * The action the user tried to perform.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $action;

	/**
	 * Constructor.
	 *
	 * @param   User    $user    The user object
	 * @param   string  $action  The action the user tried to perform.
	 *
	 * @since   1.0
	 */
	public function __construct(User $user, $action)
	{
		$this->user   = $user;
		$this->action = $action;

		parent::__construct('Authentication failure', 403);
	}

	/**
	 * Get the critical action.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Get the user object.
	 *
	 * @return  User
	 *
	 * @since   1.0
	 */
	public function getUser()
	{
		return $this->user;
	}
}
