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
 * Controller class to add an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerComment extends TrackerControllerDefault
{
	/**
	 * Execute the controller.
	 *
	 * @throws RuntimeException
	 * @throws Exception
	 *
	 * @since            1.0
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		$issueId = $this->input->getInt('id');

		if (!$issueId)
		{
			throw new RuntimeException('Missing issue id');
		}

		$modelIssue = new TrackerModelIssue;

		$issue = $modelIssue->getItem($issueId);

		if (3 != $issue->project_id)
		{
			throw new Exception('TESTING STAGE: only project id 3 is available for comments !');
		}

		$project = new JTrackerProject($issue->project_id);

		// Post the comment

		JGithubLoginhelper::comment($project, $issue->gh_id, $this->input->getHtml('comment'));

		// Retrieve the comment from GitHub and store it to the database.

		$cmd = JPATH_ROOT . '/cli/retrievecomments.php'
			. ' --auth'
			. ' --issue=' . (int) $issue->gh_id
			. ' --project=' . (int) $issue->project_id;

		exec($cmd, $output, $retVar);

		$usrReturn = $this->input->getBase64('usr_return');

		if($usrReturn)
		{
			JFactory::getApplication()->redirect(base64_decode($usrReturn));

			return false;
		}

		return parent::execute();
	}
}
