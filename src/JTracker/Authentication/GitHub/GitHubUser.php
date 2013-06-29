<?php
/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Authentication\GitHub;

use JTracker\Authentication\User;

/**
 * GitHub user class.
 *
 * @since  1.0
 */
class GitHubUser extends User
{
	/**
	 * @var    string
	 * @since  1.0
	 */
	public $avatar_url;

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $avatar;

	/**
	 * Load user data from GitHub.
	 *
	 * @param   \stdClass  $data  A JSON string from GitHub containing user data.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
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

		return $this;
	}
}
