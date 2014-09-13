<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use Joomla\Registry\Registry;
use Joomla\Github\Github;
use Joomla\Github\Http;
use Joomla\Http\HttpFactory;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Controller\AbstractTrackerController;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Login controller class for the users component
 *
 * @since  1.0
 */
class LocalLogin extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function execute()
	{
		// Local logins should only be allowed "locally"
		// @todo check also for ip v6 and other stuff...
		if ('127.0.0.1' != $_SERVER['SERVER_ADDR'])
		{
			die('Bad IP..');
		}

		$loginTypes = [
			'admin' => -1,
			'user' => -2
		];

		$application = $this->getContainer()->get('app');

		$login_type = $application->input->getCmd('login_type');

		if (false == array_key_exists($login_type, $loginTypes))
		{
			throw new \UnexpectedValueException('Unsupported login type');
		}

		$user = new GithubUser($application->getProject(), $this->getContainer()->get('db'));

		$user->id = $loginTypes[$login_type];
		$user->username = 'dummy-' . $login_type;

		// User login
		$application->setUser($user);

		$application->enqueuemessage(sprintf('Hello Mr. Dummy "%s"', $login_type));

		$application->redirect('/');

		return 'redirected...';
	}
}
