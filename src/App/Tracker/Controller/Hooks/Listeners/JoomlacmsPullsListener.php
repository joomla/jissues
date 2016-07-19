<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Table\IssuesTable;

use Joomla\Date\Date;
use Joomla\Event\Event;
use Joomla\Github\Github;
use Joomla\Http\Exception\InvalidResponseCodeException;

use Monolog\Logger;

/**
 * Event listener for the joomla-cms pull request hook
 *
 * @since  1.0
 */
class JoomlacmsPullsListener extends AbstractListener
{
	/**
	 * Event for after pull requests are created in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onPullAfterCreate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Only perform these events if this is a new pull, action will be 'opened'
		if ($arguments['action'] === 'opened')
		{
			// Check that pull requests have certain labels
			$this->checkPullLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Check if the pull request targets the master branch
			$this->checkMasterBranch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Check if the pull request targets the 2.5.x branch
			$this->check25Branch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Place the JoomlaCode ID in the issue title if it isn't already there
			$this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Send a message if there is no comment in the pull request
			$this->checkPullBody($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Set the status to pending
			$this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);

			// Close the issue if we have a Pull Request for it
			$this->checkPullRequestFixesIssue($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
		}
	}

	/**
	 * Event for after pull requests are updated in the application
	 *
	 * @param   Event  $event  Event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onPullAfterUpdate(Event $event)
	{
		// Pull the arguments array
		$arguments = $event->getArguments();

		// Only perform these events if this is a reopened pull, action will be 'reopened'
		if ($arguments['action'] === 'reopened')
		{
			// Set the status to pending
			$this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
		}

		// Check that pull requests have certain labels
		$this->checkPullLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

		// Place the JoomlaCode ID in the issue title if it isn't already there
		$this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

		// Add a RTC label if the item is in that status
		$this->checkRTClabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
	}

	/**
	 * Checks for the RTC label
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkRTClabel($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// Set some data
		$label      = 'RTC';
		$labels     = [];
		$labelIsSet = $this->checkLabel($hookData, $github, $logger, $project, $label);

		// Validation, if the status isn't RTC or the Label is set then go no further
		if ($labelIsSet === true && $table->status != 4)
		{
			// Remove the RTC label as it isn't longer set to RTC
			$labels[] = $label;
			$this->removeLabels($hookData, $github, $logger, $project, $labels);
		}

		if ($labelIsSet === false && $table->status == 4)
		{
			// Add the RTC label as it isn't already set
			$labels[] = $label;
			$this->addLabels($hookData, $github, $logger, $project, $labels);
		}
	}

	/**
	 * Checks if a pull request targets the 2.5.x branch
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function check25Branch($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		if ($hookData->pull_request->base->ref == '2.5.x')
		{
			// Post a comment on the PR informing the user of end of support and close the item
			$message  = 'Joomla! 2.5 is no longer supported.  Pull requests for this branch are no longer accepted.';
			$type     = 'unsupported branch';

			// Create the Comment
			$this->createCommentToIssue($hookData, Github $github, Logger $logger, $project, $message, $type);

			// Close the item
			$this->closeTheIssue($hookData, Github $github, Logger $logger, $project);
		}
	}

	/**
	 * Checks if a pull request targets the master branch
	 *
	 * @param   object  $hookData  Hook data payload
	 * @param   Github  $github    Github object
	 * @param   Logger  $logger    Logger object
	 * @param   object  $project   Object containing project data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkMasterBranch($hookData, Github $github, Logger $logger, $project)
	{
		if ($hookData->pull_request->base->ref == 'master')
		{
			$message  = 'Pull requests to the master branch of this repo are not accepted.';
			$message .= 'Please close this pull request and submit a new one against the staging branch.';
			$type = 'incorrect branch';

			// Create the Comment
			$this->createCommentToIssue($hookData, Github $github, Logger $logger, $project, $message, $type);
		}
	}

	/**
	 * Checks for a PR-<branch> label
	 *
	 * @param   object  $hookData  Hook data payload
	 * @param   Github  $github    Github object
	 * @param   Logger  $logger    Logger object
	 * @param   object  $project   Object containing project data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkPullLabels($hookData, Github $github, Logger $logger, $project)
	{
		// Set some data
		$prLabel              = 'PR-' . $hookData->pull_request->base->ref;
		$languageLabel        = 'Language Change';
		$unitSystemTestsLabel = 'Unit/System Tests';
		$composerLabel        = 'Composer Dependency Changed';
		$addLabels            = [];
		$removeLabels         = [];
		$prLabelSet           = $this->checkLabel($hookData, $github, $logger, $project, $prLabel);

		// Add the PR label if it isn't already set
		if (!$prLabelSet)
		{
			$addLabels[] = $prLabel;
		}

		// Get the files modified by the pull request
		try
		{
			$files = $github->pulls->getFiles($project->gh_user, $project->gh_project, $hookData->pull_request->number);
		}
		catch (InvalidResponseCodeException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving modified files for GitHub item %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				),
				['exception' => $e]
			);

			$files = [];
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error retrieving modified files for GitHub item %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				),
				['exception' => $e]
			);

			$files = [];
		}

		$composerChange   = $this->checkComposerChange($files);
		$composerLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $composerLabel);

		// Add the label if we change a Composer dependency and it isn't already set
		if ($composerChange && !$composerLabelSet)
		{
			$addLabels[] = $composerLabel;
		}
		// Remove the label if we don't change a Composer dependency
		elseif ($composerLabelSet)
		{
			$removeLabels[] = $composerLabel;
		}

		$languageChange   = $this->checkLanguageChange($files);
		$languageLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $languageLabel);

		// Add the label if we change the language files and it isn't already set
		if ($languageChange && !$languageLabelSet)
		{
			$addLabels[] = $languageLabel;
		}
		// Remove the label if we don't change the language files
		elseif ($languageLabelSet)
		{
			$removeLabels[] = $languageLabel;
		}

		$unitSystemTestsChange   = $this->checkUnitSystemTestsChange($files);
		$unitSystemTestsLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $unitSystemTestsLabel);

		// Add the label if we change the Unit/System Tests and it isn't already set
		if ($unitSystemTestsChange && !$unitSystemTestsLabelSet)
		{
			$addLabels[] = $unitSystemTestsLabel;
		}
		// Remove the label if we don't change the Unit/System Tests
		elseif ($unitSystemTestsLabelSet)
		{
			$removeLabels[] = $unitSystemTestsLabel;
		}

		// Add the labels if we need
		if (!empty($addLabels))
		{
			$this->addLabels($hookData, $github, $logger, $project, $addLabels);
		}

		// Remove the labels if we need
		if (!empty($removeLabels))
		{
			$this->removeLabels($hookData, $github, $logger, $project, $removeLabels);
		}

		return;
	}

	/**
	 * Check if we change a Composer dependency
	 *
	 * @param   array  $files  The files array
	 *
	 * @return  bool   True if we change a Composer dependency
	 *
	 * @since   1.0
	 */
	protected function checkComposerChange($files)
	{
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				// Check for file paths libraries/vendor at position 0 or filename is composer.json or composer.lock
				if (strpos($file->filename, 'libraries/vendor') === 0 || in_array($file->filename, ['composer.json', 'composer.lock']))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if we change a language file
	 *
	 * @param   array  $files  The files array
	 *
	 * @return  bool   True if we change a language file
	 *
	 * @since   1.0
	 */
	protected function checkLanguageChange($files)
	{
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				// Check for file paths administrator/language, installation/language, and language at position 0
				if (strpos($file->filename, 'administrator/language') === 0
					|| strpos($file->filename, 'installation/language') === 0
					|| strpos($file->filename, 'language') === 0)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if we change the Unit/System Test tests
	 *
	 * @param   array  $files  The files array
	 *
	 * @return  bool   True if we change a Unit/System Test file
	 *
	 * @since   1.0
	 */
	protected function checkUnitSystemTestsChange($files)
	{
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				// Check for files & paths regarding the Unit/System Tests
				if (strpos($file->filename, 'tests') === 0
					|| $file->filename == '.travis.yml'
					|| $file->filename == 'phpunit.xml.dist'
					|| $file->filename == 'travisci-phpunit.xml')
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Updates the local application status for an item
	 *
	 * @param   Logger       $logger   Logger object
	 * @param   object       $project  Object containing project data
	 * @param   IssuesTable  $table    Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setPending(Logger $logger, $project, IssuesTable $table)
	{
		if ($table->status == 3)
		{
			return;
		}

		// Reset the issue status to pending and try updating the database
		try
		{
			$table->save(['status' => 3]);
		}
		catch (\InvalidArgumentException $e)
		{
			$logger->error(
				sprintf(
					'Error setting the status to pending in local application for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$table->issue_number
				),
				['exception' => $e]
			);
		}
		catch (\RuntimeException $e)
		{
			$logger->error(
				sprintf(
					'Error setting the status to pending in local application for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$table->issue_number
				),
				['exception' => $e]
			);
		}
	}

	/**
	 * Updates a pull request title to include the JoomlaCode ID if it exists
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function updatePullTitle($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// If the title already has the ID in it, then no need to do anything here
		if (preg_match('/\[#([0-9]+)\]/', $hookData->pull_request->title, $matches))
		{
			return;
		}

		// If we don't have a foreign ID, we can't do anything here
		if (is_null($table->foreign_number))
		{
			return;
		}

		// Define the new title
		$title = '[#' . $table->foreign_number . '] ' . $table->title;

		try
		{
			$github->pulls->edit(
				$project->gh_user, $project->gh_project, $hookData->pull_request->number, $title
			);

			// Post the new label on the object
			$logger->info(
				sprintf(
					'Updated the title for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				)
			);
		}
		catch (InvalidResponseCodeException $e)
		{
			$logger->error(
				sprintf(
					'Error updating the title for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				),
				['exception' => $e]
			);

			// Don't change the title locally
			return;
		}
		catch (\DomainException $e)
		{
			$logger->error(
				sprintf(
					'Error updating the title for GitHub pull request %s/%s #%d',
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number
				),
				['exception' => $e]
			);

			// Don't change the title locally
			return;
		}

		// Update the local title now
		try
		{
			$data = ['title' => $title];
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$logger->error(
				sprintf(
					'Error updating the title for issue %s/%s #%d on the tracker',
					$project->gh_user,
					$project->gh_project,
					$hookData->issue->number
				),
				['exception' => $e]
			);
		}
	}

	/**
	 * Checks if a pull request have a comment
	 *
	 * @param   object  $hookData  Hook data payload
	 * @param   Github  $github    Github object
	 * @param   Logger  $logger    Logger object
	 * @param   object  $project   Object containing project data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkPullBody($hookData, Github $github, Logger $logger, $project)
	{
		if ($hookData->pull_request->body == '')
		{
			// Post a comment on the PR asking to add a description
			$addLabels                       = [];
			$testInstructionsMissingLabel    = 'Test instructions missing';
			$testInstructionsMissingLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $testInstructionsMissingLabel);

			// Add the Test instructions missing label if it isn't already set
			if (!$testInstructionsMissingLabelSet)
			{
				$addLabels[] = $testInstructionsMissingLabel;
				$this->addLabels($hookData, $github, $logger, $project, $addLabels);
			}

			$message  = 'Please add more information to your issue.'
			$message .= 'Without test instructions and/or any description we will close this issue within 4 weeks. Thanks.';
			$type = 'a no description';
			$this->createCommentToIssue($hookData, Github $github, Logger $logger, $project, $message, $type);
		}
	}

	/**
	 * Checks if a pull request has a refference to an issue with the template
	 * `Pull Request for Issue # .` if yes it close the issue id and add a comment why he close it.
	 *
	 * @param   object       $hookData  Hook data payload
	 * @param   Github       $github    Github object
	 * @param   Logger       $logger    Logger object
	 * @param   object       $project   Object containing project data
	 * @param   IssuesTable  $table     Table object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function checkPullRequestFixesIssue($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// We want the ID. Text to check is: `Pull Request for Issue # .`
		$body   = $hookData->pull_request->body;
		$id  = explode('#', $body);
		$id  = explode('.', $id[1]);
		$id  = (int) trim($id[0]);
		$length = strlen($id);

		// If we have an integer with lenth 4 between the # and the . we have an Id to check
		if (is_int($id) && $length == 4)
		{
			// Load the table for the ID
			$checktable = (new IssuesTable($this->db))->load($id);

			// If the issue is closed we don't need to do anything
			if ($checktable->status == 10)
			{
				// Issue is closed nothing to do
				return;
			}

			// Close the issue
			$this->closeTheIssue($hookData, Github $github, Logger $logger, $project, $id);
		}

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

		// Add the message that this Issue gets closed because we have a Pull Request.
		$message = 'Closed as we have a PR for testing here #' . $issueNumber . ' - Thanks';
		$type = 'the there is a Pull Request';
		$this->createCommentToIssue($hookData, Github $github, Logger $logger, $project, $message, $type);

		// Lets add `realted_to` property

		if (!isset($table->rel_number) && !isset($checktable->rel_type)
		{
			$data = [
				'rel_number'  => $id,
				'rel_type'    => 4,
			];

			// Try to save the item
			try
			{
				$table->save($data);
			}
			catch (\Exception $e)
			{
				$logger->error(
					sprintf(
						'Error updating the rel_number and rel_type for issue %s/%s #%d on the tracker',
						$project->gh_user,
						$project->gh_project,
						$issueNumber
					),
					['exception' => $e]
				);
			}
		}
	}
}
