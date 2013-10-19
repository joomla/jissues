<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Markdown;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

/**
 * Controller class to render a text entry in GitHub Flavored Markdown format.
 *
 * @since  1.0
 */
class Preview extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		// Only registered users are able to use the preview using their credentials.
		if (!$this->getApplication()->getUser()->id)
		{
			throw new \Exception('not auth..');
		}

		$text = $this->getInput()->get('text', '', 'raw');

		if (!$text)
		{
			throw new \Exception('Nothing to preview...');
		}

		$project = $this->getApplication()->getProject();

		/* @type \Joomla\Github\Github $github */
		$github = Container::retrieve('gitHub');

		$this->response->data = $github->markdown->render(
			$text,
			'gfm',
			$project->gh_user . '/' . $project->gh_project
		);
	}
}
