<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication\Exception;

use Joomla\Tracker\Authentication\User;

/**
 * Class AuthenticationException
 *
 * @since  1.0
 */
class AuthenticationException extends \Exception
{
	/**
	 * The user object.
	 *
	 * @var User
	 */
	public $user;

	/**
	 * The action the user tried to perform.
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Constructor.
	 *
	 * @param   User    $user    The user object
	 * @param   string  $action  The action the user tried to perform.
	 */
	public function __construct(User $user, $action)
	{
		$this->user = $user;
		$this->action = $action;
	}
}
