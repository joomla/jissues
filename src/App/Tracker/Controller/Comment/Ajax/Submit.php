<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Comment\Ajax;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Helper\GitHubHelper;

/**
 * Add comments controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('create');

		$comment     = $application->input->get('text', '', 'raw');
		$issueNumber = $application->input->getInt('issue_number');
		$project     = $application->getProject();

		if (!$issueNumber)
		{
			throw new \Exception('No issue number received.');
		}

		if (!$comment)
		{
			throw new \Exception('You should write a comment first...');
		}

		$gitHubHelper = new GitHubHelper($this->getContainer()->get('gitHub'));

		$comment .= $gitHubHelper->getApplicationComment($application, $project, $issueNumber);

		$this->response->data = $gitHubHelper->addComment(
			$project, $issueNumber, $comment, $application->getUser()->username, $this->getContainer()->get('db')
		);

		$this->response->message = g11n3t('Your comment has been submitted');
	}
}
