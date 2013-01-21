<?php
/**
 * @package     JTracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * HTML Utility class for GitHub
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
class JHtmlGithub
{
	public static function loginButton($text = 'Login with GitHub', $redirect = '')
	{
		$gh_client_id = JFactory::getConfig()->get('github_client_id');

		if(!$gh_client_id)
		{
			return 'Please set your GitHub client id in configuration.php';
		}

		$uri = JUri::getInstance();

		if($redirect)
		{
			$uri->parse($redirect);
		}

		$usrRedirect = base64_encode((string) $uri);

		$uri->setVar('option', 'com_users');
		$uri->setVar('task', 'ghlogin');
		$uri->setVar('usr_redirect', $usrRedirect);

		$redirect = (string) $uri;

		$url = 'https://github.com/login/oauth/authorize?scope=public_repo'
			. '&client_id=' . $gh_client_id
			. '&redirect_uri=' . urlencode($redirect)
		;

		return JHtml::link($url, $text, 'class="btn btn-primary"');
	}
}
