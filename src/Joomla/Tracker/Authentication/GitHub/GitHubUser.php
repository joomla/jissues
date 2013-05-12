<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication\GitHub;

use Joomla\Tracker\Authentication\User;

/**
 * GitHub user class.
 *
 * @package     JTracker
 * @subpackage  GitHub
 * @since       1.0
 */
class GitHubUser extends User
{
	public $avatar_url;

	/**
	 * Load user data from GitHub.
	 *
	 * @param   \stdClass  $data  A JSON string from GitHub containing user data.
	 *
	 * @throws \RuntimeException
	 *
	 * @return $this
	 */
	public function loadGitHubData(\stdClass $data)
	{
		if (!isset($data->login))
		{
			throw new \RuntimeException('Missing login');
		}

		foreach ($data as $k => $v)
		{
			if (property_exists($this, $k) && false == in_array($k, array('id')))
			{
				$this->$k = $v;
			}
		}

		$this->username = $data->login;

		if (!$this->email)
		{
			$this->email = 'email@example.com';
		}

		if (!$this->name)
		{
			$this->name = $this->username;
		}

		return $this;
	}
}
