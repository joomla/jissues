<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Authentication\Exception;

use JTracker\Authentication\User;

/**
 * Class AuthenticationException.
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
		$this->code   = 403;
	}

	/**
	 * Get the critical action.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Get the user object.
	 *
	 * @since  1.0
	 * @return \JTracker\Authentication\User
	 */
	public function getUser()
	{
		return $this->user;
	}
}
