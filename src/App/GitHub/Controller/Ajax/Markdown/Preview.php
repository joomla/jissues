<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Markdown;

use JTracker\Controller\AbstractAjaxController;

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
		if (!$this->getContainer()->get('app')->getUser()->id)
		{
			throw new \Exception('not auth..');
		}

		$text = $this->getContainer()->get('app')->input->get('text', '', 'raw');

		if (!$text)
		{
			throw new \Exception(g11n3t('Nothing to preview...'));
		}

		$project = $this->getContainer()->get('app')->getProject();

		/** @var \Joomla\Github\Github $github */
		$github = $this->getContainer()->get('gitHub');

		$this->response->data = $github->markdown->render(
			$text,
			'gfm',
			$project->gh_user . '/' . $project->gh_project
		);
	}
}
