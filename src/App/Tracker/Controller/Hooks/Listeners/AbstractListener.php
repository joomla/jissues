<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Projects\TrackerProject;

use Joomla\Github\Github;
use Joomla\Http\Exception\InvalidResponseCodeException;

use JTracker\Github\DataType\Commit\Status;

use Monolog\Logger;

/**
 * Abstract listener class for custom Listeners
 *
 * @since  1.0
 */
abstract class AbstractListener
{
	/**
	 * Check if label already exists
	 *
	 * @param   object  $hookData    Hook data payload
	 * @param   Github  $github      Github object
	 * @param   Logger  $logger      Logger object
	 * @param   object  $project     Object containing project data
	 * @param   string  $checkLabel  The label to check
	 *
	 * @return  bool    True if the label already exists
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function checkLabel($hookData, Github $github, Logger $logger, $project, $checkLabel)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueNumber($hookData);

		if ($issueNumber === null)
		{
			$message = sprintf('Error retrieving issue number for %s/%s', $project->gh_user, $project->gh_project);

			$logger->error($message);

			throw new \RuntimeException($message);
		}

		// Get the labels for the pull's issue
		try
		{
			$labels = $github->issues->get($project->gh_user, $project->gh_project, $issueNumber)->labels;
		}
		catch (InvalidResponseCodeException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);

			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving labels for GitHub item %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);

			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		// Check if the label present that return true
		if (count($labels) > 0)
		{
			foreach ($labels as $label)
			{
				if ($label->name == $checkLabel)
				{
					return true;
				}
			}
		}

		// Else return false
		return false;
	}

	/**
	 * Remove Labels
	 *
	 * @param   object  $hookData      Hook data payload
	 * @param   Github  $github        Github object
	 * @param   Logger  $logger        Logger object
	 * @param   object  $project       Object containing project data
	 * @param   array   $removeLabels  The labels to remove
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function removeLabels($hookData, Github $github, Logger $logger, $project, $removeLabels)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueNumber($hookData);

		if ($issueNumber === null)
		{
			$message = sprintf('Error retrieving issue number for %s/%s', $project->gh_user, $project->gh_project);

			$logger->error($message);

			throw new \RuntimeException($message);
		}

		// Only try to remove labels if the array isn't empty
		if (!empty($removeLabels))
		{
			// The foreach is needed as we have no array support on the `removeFromIssue` method
			foreach ($removeLabels as $removeLabel)
			{
				try
				{
					$github->issues->labels->removeFromIssue(
						$project->gh_user, $project->gh_project, $issueNumber, $removeLabel
					);

					// Post the new label on the object
					$logger->info(
						sprintf(
							'Removed %s label to %s/%s #%d',
							$removeLabel,
							$project->gh_user,
							$project->gh_project,
							$issueNumber
						)
					);
				}
				catch (InvalidResponseCodeException $e)
				{
					$logger->error(
						sprintf(
							'Error removing the %s label from GitHub pull request %s/%s #%d',
							$removeLabel,
							$project->gh_user,
							$project->gh_project,
							$issueNumber
						),
						['exception' => $e]
					);

					throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
				}
				catch (\DomainException $e)
				{
					$logger->error(
						sprintf(
							'Error removing the %s label from GitHub pull request %s/%s #%d',
							$removeLabel,
							$project->gh_user,
							$project->gh_project,
							$issueNumber
						),
						['exception' => $e]
					);

					throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
				}
			}
		}
	}

	/**
	 * Get the correct issue ID if it is a Pull or Issue
	 *
	 * @param   object  $hookData  Hook data payload
	 *
	 * @return  mixed   The Issue number or null if no issue number found in hook data
	 *
	 * @since   1.0
	 */
	protected function getIssueNumber($hookData)
	{
		if (isset($hookData->pull_request->number))
		{
			return $hookData->pull_request->number;
		}

		if (isset($hookData->issue->number))
		{
			return $hookData->issue->number;
		}

		return null;
	}

	/**
	 * Add Labels
	 *
	 * @param   object  $hookData   Hook data payload
	 * @param   Github  $github     Github object
	 * @param   Logger  $logger     Logger object
	 * @param   object  $project    Object containing project data
	 * @param   array   $addLabels  The labels to add
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 *
	 * @since   1.0
	 */
	protected function addLabels($hookData, Github $github, Logger $logger, $project, $addLabels)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueNumber($hookData);

		if ($issueNumber === null)
		{
			$logger->error(
				sprintf(
					'Error retrieving issue number for %s/%s',
					$project->gh_user,
					$project->gh_project
				)
			);

			throw new \RuntimeException('Error retrieving issue number for ' . $project->gh_user . '/' . $project->gh_project);
		}

		// Only try to add labels if the array isn't empty
		if (!empty($addLabels))
		{
			try
			{
				$github->issues->labels->add(
					$project->gh_user, $project->gh_project, $issueNumber, $addLabels
				);

				// Post the new label on the object
				$logger->info(
					sprintf(
						'Added %s labels to %s/%s #%d',
						count($addLabels),
						$project->gh_user,
						$project->gh_project,
						$issueNumber
					)
				);
			}
			catch (InvalidResponseCodeException $e)
			{
				$logger->error(
					sprintf(
						'Error adding labels to GitHub pull request %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$issueNumber
					),
					['exception' => $e]
				);

				throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
			}
			catch (\DomainException $e)
			{
				$logger->error(
					sprintf(
						'Error adding labels to GitHub pull request %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$issueNumber
					),
					['exception' => $e]
				);

				throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * Create a status on GitHub.
	 *
	 * @param   Github          $gitHub       The GitHub object.
	 * @param   TrackerProject  $project      The Project object.
	 * @param   integer         $issueNumber  The issue number.
	 * @param   Status          $status       The Status object.
	 * @param   string          $sha          The commit SHA.
	 *
	 * @since  1.0
	 * @return object
	 */
	protected function createStatus(Github $gitHub, TrackerProject $project, $issueNumber, Status $status, $sha = '')
	{
		if (!$sha)
		{
			// Get the SHA of the last commit.
			$pullRequest = $gitHub->pulls->get(
				$project->gh_user, $project->gh_project, $issueNumber
			);

			$sha = $pullRequest->head->sha;
		}

		return $gitHub->repositories->statuses->create(
			$project->gh_user, $project->gh_project, $sha,
			$status->state, $status->targetUrl, $status->description, $status->context
		);
	}

	/**
	 * Create a comment to an Issue or Pull Request
	 *
	 * @param   object  $hookData   Hook data payload
	 * @param   Github  $github     Github object
	 * @param   Logger  $logger     Logger object
	 * @param   object  $project    Object containing project data
	 * @param   string  $message    Contains the message to be added
	 * @param   string  $type       Contains the type of the message (that get added to the log)
	 *
	 * @since  1.0
	 * @return void
	 */
	protected function createCommentToIssue($hookData, Github $github, Logger $logger, $project, $message, $type)
	{
		// The Github ID if we have a pull or issue so that method can handle both
		$issueNumber = $this->getIssueNumber($hookData);

		if ($issueNumber === null)
		{
			$logger->error(
				sprintf(
					'Error retrieving issue number for %s/%s',
					$project->gh_user,
					$project->gh_project
				)
			);

			throw new \RuntimeException('Error retrieving issue number for ' . $project->gh_user . '/' . $project->gh_project);
		}

		$appNote = sprintf(
			'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
			'https://github.com/joomla/jissues', 'J!Tracker'
		);

		// Add the App Note to the message
		$message = $message . $appNote;

		// Post the comment on the PR/Issue
		try
		{
			$github->issues->comments->create(
				$project->gh_user,
				$project->gh_project,
				$issueNumber,
				$message
			);

			// Log the activity
			$logger->info(
				sprintf(
					'Added ' . $type . ' comment to %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				)
			);
		}
		catch (InvalidResponseCodeException $e)
		{
			$logger->error(
				sprintf(
					'Error posting comment to GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error posting comment to GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);
		}
	}

	/**
	 * Create a status on GitHub.
	 *
	 * @param   object  $hookData   Hook data payload
	 * @param   Github  $github     Github object
	 * @param   Logger  $logger     Logger object
	 * @param   object  $project    Object containing project data
	 * @param   integer $id         The issue ID to close if not set we will use the current ID.
	 *
	 * @since  1.0
	 * @return void
	 */
	protected function closeTheIssue($hookData, Github $github, Logger $logger, $project, $id = null)
	{
		// Check for the optional paramenter that contains the ID to close
		if ($id === null)
		{
			// There is no $id set so we get the current issue ID out of te hook data
			// The Github ID if we have a pull or issue so that method can handle both
			$issueNumber = $this->getIssueNumber($hookData);

			if ($issueNumber === null)
			{
				$logger->error(
					sprintf(
						'Error retrieving issue number for %s/%s',
						$project->gh_user,
						$project->gh_project
					)
				);

				throw new \RuntimeException('Error retrieving issue number for ' . $project->gh_user . '/' . $project->gh_project);
			}
		}
		else
		{
			// Lets set the issueNumber
			$issueNumber = $id;
		}

		// Close the item
		try
		{
			$github->pulls->edit(
				$project->gh_user, $project->gh_project, $issueNumber, null, null, 'closed'
			);

			// Update the local item now
			try
			{
				// TODO - We'll need to inject the DB object at some point
				$data = [
					'status'      => 10,
					'closed_date' => (new Date)->format('Y-m-d H:i:s'),
					'closed_by'   => $this->getGithubBotName($project);
				];

				$table->save($data);
			}
			catch (\Exception $e)
			{
				$logger->error(
					sprintf(
						'Error updating the state for issue %s/%s #%d on the tracker',
						$project->gh_user,
						$project->gh_project,
						$issueNumber
					),
					['exception' => $e]
				);
			}
		}
		catch (InvalidResponseCodeException $e)
		{
			$logger->error(
				sprintf(
					'Error closing GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error closing GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$issueNumber
				),
				['exception' => $e]
			);
		}
	}

	/**
	 * Return the currently configured bot account. Fallback is 'joomla-bot'
	 *
	 * @param   object  $project  Object containing project data
	 *
	 * @return  string  The currently configured bot account
	 *
	 * @since   1.0
	 *
	 */
	protected function getGithubBotName($project)
	{
		// Look if we have a bot user configured
		if ($project->getGh_Editbot_User())
		{
			// Retrun the user name
			return $project->getGh_Editbot_User();
		}

		// Retun "joomla-bot" as fallback
		return 'joomla-bot';
	}
}
