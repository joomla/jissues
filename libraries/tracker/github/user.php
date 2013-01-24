<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * GitHub user class.
 *
 * @package     JTracker
 * @subpackage  GitHub
 * @since       1.0
 */
class JGithubUser extends JUser
{
	public $avatar_url;

	/**
	 * Constructor.
	 *
	 * @param   stdClass  $data  A JSON string from GitHub containing user data.
	 *
	 * @throws RuntimeException
	 */
	public function __construct($data)
	{
		if (!$data)
		{
			throw new RuntimeException(sprintf('%1$s - Invalid data', __METHOD__));
		}

		foreach ($data as $k => $v)
		{
			if (property_exists($this, $k)
				&& false == in_array($k, array('id'))
			)
			{
				$this->$k = $v;
			}
		}

		$this->username = isset($data->login) ? $data->login : '';

		if (!$this->email)
		{
			$this->email = 'email@example.com';
		}

		if (!$this->name)
		{
			$this->name = $this->username;
		}

		$id = JUserHelper::getUserId($this->username);

		return parent::__construct($id);
	}
}
