<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\HTML;

use Joomla\Factory;
use Joomla\Uri\Uri;

use Joomla\Tracker\Authentication\User;
use Joomla\Tracker\HTML\Html;

/**
 * Class HtmlGitHub.
 *
 * @since  1.0
 */
final class HtmlGitHub
{
	/**
	 * Display a login button.
	 *
	 * @param   string  $gh_client_id  GitHub client id.
	 * @param   string  $text          Button text.
	 * @param   string  $redirect      Redirect link.
	 *
	 * @return string
	 */
	public static function loginButton($gh_client_id, $text = 'Login with GitHub', $redirect = null)
	{
		if (!$gh_client_id)
		{
			return 'Please set your GitHub client id in configuration.php';
		}

		$redirect = $redirect ? : Factory::$application->get('uri.request') . 'login';

		$uri = new Uri($redirect);

		$usrRedirect = base64_encode((string) $uri);

		$uri->setVar('usr_redirect', $usrRedirect);

		$redirect = (string) $uri;

		// Use "raw URI" here to partial encode the url.
		$url = 'https://github.com/login/oauth/authorize?scope=public_repo'
			. '&client_id=' . $gh_client_id
			. '&redirect_uri=' . urlencode($redirect);

		return Html::link($url, $text, 'class="btn btn-primary"');
	}

	/**
	 * Display an avatar image.
	 *
	 * @param   User     $user      User object.
	 * @param   integer  $maxWidth  Max image width.
	 *
	 * @return string
	 */
	public static function avatar(User $user, $maxWidth = 0)
	{
		return '[avatar]';

		// @todo re-enable ?

		static $avatar;

		$imageBase = 'media/jtracker/avatars';

		if (!$avatar)
		{
			jimport('joomla.filesystem.folder');

			$files = JFolder::files(JPATH_ROOT . '/' . $imageBase, '^' . $user->username . '\.');

			$avatar = (isset($files[0])) ? $files[0] : 'amor.png';
		}

		$attribs = array();

		$attribs ['title'] = $user->username;

		if ($maxWidth)
		{
			$attribs['height'] = $maxWidth;
			$attribs['width']  = $maxWidth;
		}

		return JHtml::image($imageBase . '/' . $avatar, $user->username, $attribs);
	}
}
