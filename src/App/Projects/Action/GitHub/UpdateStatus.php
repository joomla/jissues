<?php
/**
 * Part of the Joomla! Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Action\GitHub;

use App\Tracker\Model\IssueModel;

use JTracker\Github\DataType\Commit\Status;

/**
 * Class UpdateStatus
 *
 * @since  1.0
 */
class UpdateStatus extends AbstractGitHub
{
	/**
	 * The pull request object.
	 * @var   object
	 */
	private $pullRequest;

	/**
	 * Run the action.
	 *
	 * @param   array   $params        Parameters.
	 * @param   object  $actionParams  Parameters for the action.
	 *
	 * @since   1.0
	 *
	 * @return mixed
	 */
	public function run(array $params, $actionParams)
	{
		$this->checkParams($params, ['GitHub', 'pullRequest']);

		$this->pullRequest = $params['pullRequest'];
		$this->setGitHub($params['GitHub']);

		foreach ($actionParams as $type => $actionParam)
		{
			if ('1' != $actionParam->active)
			{
				continue;
			}

			if (false == method_exists($this, $type))
			{
				throw new \UnexpectedValueException(
					sprintf(
						'Method"%1$s"does not exist in class"%2$s"', $type, __CLASS__
					)
				);
			}

			unset($actionParam->active);

			$status = $this->$type($actionParam);

			if ($status->state)
			{
				$this->createStatus($this->pullRequest->number, $status);
			}
		}
	}

	/**
	 * Get a merge status for human test results.
	 *
	 * @param   object  $actionParam  The action params object.
	 *
	 * @since 1.0
	 *
	 * @return Status
	 */
	private function humanTestResults($actionParam)
	{
		$model = new IssueModel($this->database);

		$model->setProject($this->project);

		$issue = $model->getItem($this->pullRequest->number);

		$cntSuccess = count($issue->testsSuccess);
		$cntFailure = count($issue->testsFailure);

		$status = new Status;

		foreach ($actionParam as $type => $checks)
		{
			$comp_success = $this->cleanComp($checks->comp_success);
			$comp_failure = $this->cleanComp($checks->comp_failure);

			$stateSuccess = false;
			$stateFailure = false;

			if ($comp_success)
			{
				$checkSuccess = $cntSuccess . $comp_success . (int) $checks->cnt_success;

				if (eval('return ' . $checkSuccess . ';'))
				{
					$stateSuccess = true;
				}
			}

			if ($comp_failure)
			{
				$checkFailure = $cntFailure . $comp_failure . (int) $checks->cnt_failure;

				if (eval('return ' . $checkFailure . ';'))
				{
					$stateFailure = true;
				}
			}

			if ($stateSuccess && $stateFailure)
			{
				$status->state = $type;
				$status->description = sprintf('Human Test Results: %1$d Successful %2$d Failed.', $cntSuccess, $cntFailure);
				$status->context = 'JTracker/HumanTestResults';

				// @todo - where to get the URL from a CLI script?
				$status->targetUrl = '' . $this->pullRequest->number;

				return $status;
			}
		}

		return $status;
	}

	/**
	 * Create a GitHub merge status for the last commit in a PR.
	 *
	 * @param   integer  $issueNumber  The issue number.
	 * @param   Status   $status       The status object.
	 * @param   string   $sha          The SHA of the corresponding commit.
	 *
	 * @since    1.0
	 *
	 * @return  object
	 */
	private function createStatus($issueNumber, Status $status, $sha = '')// $state, $targetUrl, $description, $context)
	{
		if (!$sha)
		{
			// Get the SHA of the last commit.
			$pullRequest = $this->getGitHub()->pulls->get(
				$this->project->gh_user, $this->project->gh_project, $issueNumber
			);

			$sha = $pullRequest->head->sha;
		}

		return $this->getGitHub()->repositories->statuses->create(
			$this->project->gh_user, $this->project->gh_project, $sha,
			$status->state, $status->targetUrl, $status->description, $status->context
		);
	}

	/**
	 * Clean up a string.
	 *
	 * @param   string  $string  The string to clean.
	 *
	 * @since    1.0
	 *
	 * @return  string
	 */
	private function cleanComp($string)
	{
		$string = preg_replace('/[^=<>]/', '', $string);

		if ('=' == $string)
		{
			$string = '==';
		}

		return $string;
	}
}
