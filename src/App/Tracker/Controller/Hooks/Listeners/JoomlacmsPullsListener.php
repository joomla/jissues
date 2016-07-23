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
	 * The Tracker Categories that gets handled based on the files that changed by a pull request
	 * Changes on the pull request only affect this categories
	 *
	 * @since   1.0
	 */
	protected $trackerHandledCategories = [
		// JavaScript
		'1' => ['.js$'],
		// Postgresql
		'2' => [
			'^administrator\/components\/com_admin\/sql\/updates\/postgresql',
			'^installation\/sql\/postgresql',
		],
		// MS SQL
		'3' => [
			'^administrator\/components\/com_admin\/sql\/updates\/sqlazure',
			'^installation\/sql\/sqlazure',
		],
		// External Library
		'4' => [
			'^libraries\/fof\/',
			'^libraries\/idna_convert\/',
			'^libraries\/phpass\/',
			'^libraries\/phputf8\/',
			'^libraries\/simplepie\/',
			'^libraries\/vendor\/',
			'^media\/editors\/codemirror',
			'^media\/editors\/tinymce',
			'composer.json',
			'composer.lock',
		],
		// SQL
		'10' => [
			'^administrator\/components\/com_admin\/sql\/updates',
			'^installation\/sql',
		],
		// Libaries
		'12' => ['^libraries\/'],
		// Modules
		'13' => [
			'^administrator\/modules\/',
			'^modules\/',
		],
		// Unit Tests
		'14' => [
			'^tests',
			'.travis.yml',
			'phpunit.xml.dist',
			'travisci-phpunit.xml',
		],
		// Layout
		'15' => ['^layouts\/'],
		// Tags
		'16' => [
			'^administrator\/components\/com_tags',
			'^components\/com_tags',
		],
		// CLI
		'18' => ['^cli\/'],
		// Administration
		'23' => ['^administrator\/'],
		// Front End
		'24' => [
			'^components\/',
			'^modules\/',
			'^plugins\/',
			'^templates\/',
		],
		// Installation
		'25' => ['^installation\/'],
		// Language & Strings
		'27' => [
			'^administrator\/language',
			'^installation\/language',
			'^language',
		],
		// Plugins
		'28' => ['^plugins\/'],
		// Components
		'29' => [
			'^administrator\/components\/',
			'^components\/',
		],
		// Site Template
		'30' => ['^templates\/'],
		// Admin templates
		'31' => ['^administrator\/templates\/'],
		// Media Manager
		'35' => [
			'^administrator\/components\/com_media',
			'^components\/com_media',
		],
		// Repository
		'36' => [
			'^build\/',
			'^.github\/',
			'.gitignore',
			'CONTRIBUTING.md',
			'README.md',
			'README.txt',
			'build.xml',
		],
	];

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

			// Check the Categories based on the files that gets changed
			$this->checkCategories($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
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

		// Check the Categories based on the files that gets changed
		$this->checkCategories($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
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
			try
			{
				$appNote = sprintf(
					'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
					'https://github.com/joomla/jissues', 'J!Tracker'
				);

				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Joomla! 2.5 is no longer supported.  Pull requests for this branch are no longer accepted.' . $appNote
				);

				$github->pulls->edit(
					$project->gh_user, $project->gh_project, $hookData->pull_request->number, null, null, 'closed'
				);

				// Update the local item now
				try
				{
					// TODO - We'll need to inject the DB object at some point
					$data = [
						'status'      => 10,
						'closed_date' => (new Date)->format('Y-m-d H:i:s'),
						'closed_by'   => 'jissues-bot'
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
							$hookData->pull_request->number
						),
						['exception' => $e]
					);
				}

				// Log the activity
				$logger->info(
					sprintf(
						'Added unsupported branch comment to %s/%s #%d',
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
						'Error posting comment to GitHub pull request %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number
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
						$hookData->pull_request->number
					),
					['exception' => $e]
				);
			}
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
			// Post a comment on the PR asking to open a pull against staging
			try
			{
				$appNote = sprintf(
					'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
					'https://github.com/joomla/jissues', 'J!Tracker'
				);

				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Pull requests to the master branch of this repo are not accepted.  '
					. 'Please close this pull request and submit a new one against the staging branch.' . $appNote
				);

				// Log the activity
				$logger->info(
					sprintf(
						'Added incorrect branch comment to %s/%s #%d',
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
						'Error posting comment to GitHub pull request %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number
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
						$hookData->pull_request->number
					),
					['exception' => $e]
				);
			}
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
		$files = $this->getChangedFilesByPullRequest($hookData, $github, $logger, $project);

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
			try
			{
				$addLabels                       = [];
				$testInstructionsMissingLabel    = 'Test instructions missing';
				$testInstructionsMissingLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $testInstructionsMissingLabel);

				// Add the Test instructions missing label if it isn't already set
				if (!$testInstructionsMissingLabelSet)
				{
					$addLabels[] = $testInstructionsMissingLabel;
					$this->addLabels($hookData, $github, $logger, $project, $addLabels);
				}

				$appNote = sprintf(
					'<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
					'https://github.com/joomla/jissues', 'J!Tracker'
				);

				$github->issues->comments->create(
					$project->gh_user,
					$project->gh_project,
					$hookData->pull_request->number,
					'Please add more information to your issue. Without test instructions and/or any description we will close this issue within 4 weeks. Thanks.'
					. $appNote
				);

				// Log the activity
				$logger->info(
					sprintf(
						'Added a no description comment to %s/%s #%d',
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
						'Error posting comment to GitHub pull request %s/%s #%d',
						$project->gh_user,
						$project->gh_project,
						$hookData->pull_request->number
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
						$hookData->pull_request->number
					),
					['exception' => $e]
				);
			}
		}
	}

	/**
	 * Checks the changed files and add based on that data a category (if possible)
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
	protected function checkCategories($hookData, Github $github, Logger $logger, $project, IssuesTable $table)
	{
		// The current categories for the PR.
		$currentCategories = $this->getCategories($hookData, $logger, $project, $table);

		// Hold the category ids that are added to the issue but not handled by the tracker to readd it later
		$categoriesThatShouldStay = array_diff($currentCategories, array_keys($this->trackerHandledCategories));

		// Get the files tha gets changed with this Pull Request
		$files = $this->getChangedFilesByPullRequest($hookData, $github, $logger, $project);

		// The new categories based on the current code of the PR
		$newCategories = $this->checkFilesAndAssignCategory($files);

		// Merge the current and the new categories
		$categories = array_merge($newCategories, $categoriesThatShouldStay);

		// Make sure we have no duplicate entries here
		$categories = array_unique($categories);

		// Add the categories we need
		$this->setCategories($hookData, $logger, $project, $table, $categories);
	}

	/**
	 * Check the changed files and return the correct category if possible.
	 *
	 * @param   array  $files  The files array
	 *
	 * @return  array  Category alias of the category we want to add
	 *
	 * @since   1.0
	 */
	protected function checkFilesAndAssignCategory($files)
	{
		$categories = [];

		if (empty($files))
		{
			// Nothing to do here...
			return [];
		}

		foreach ($files as $file)
		{
			foreach ($this->trackerHandledCategories as $catIndex => $checks)
			{
				if (in_array($catIndex, $categories))
				{
					continue;
				}

				foreach ($checks as $check)
				{
					if (preg_match('/' . $check . '/', $file->filename))
					{
						$categories[] = $catIndex;

						continue 2;
					}
				}
			}
		}

		return $categories;
	}
}
