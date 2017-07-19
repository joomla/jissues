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
	 * The Tracker Categories that are handled based on the files that changed by a pull request.
	 *
	 * The category index is provided as the key while the values are containing regular expressions matching the file paths.
	 *
	 * @since   1.0
	 */
	protected $trackerHandledCategories = [
		// JavaScript
		'1' => ['.js$'],
		// Postgresql
		'2' => [
			'^administrator/components/com_admin/sql/updates/postgresql',
			'^installation/sql/postgresql',
			'^libraries/joomla/database/(.*)/postgresql.php',
		],
		// MS SQL
		'3' => [
			'^administrator/components/com_admin/sql/updates/sqlazure',
			'^installation/sql/sqlazure',
			'^libraries/joomla/database/(.*)/sqlazure.php',
			'^libraries/joomla/database/(.*)/sqlsrv.php',
		],
		// External Library
		'4' => [
			'^libraries/fof/',
			'^libraries/idna_convert/',
			'^libraries/phpass/',
			'^libraries/phputf8/',
			'^libraries/simplepie/',
			'^libraries/vendor/',
			'^media/editors/codemirror',
			'^media/editors/tinymce',
			'composer.json',
			'composer.lock',
		],
		// SQL
		'10' => [
			'^administrator/components/com_admin/sql/updates',
			'^installation/sql',
		],
		// Libaries
		'12' => ['^libraries/'],
		// Modules
		'13' => [
			'^administrator/modules/',
			'^modules/',
		],
		// Unit Tests
		'14' => [
			'^tests',
			'.travis.yml',
			'phpunit.xml.dist',
			'travisci-phpunit.xml',
			'appveyor-phpunit.xml',
			'.appveyor.yml',
			'.drone.yml',
			'karma.conf.js',
		],
		// Layout
		'15' => ['^layouts/'],
		// Tags
		'16' => [
			'^administrator/components/com_tags',
			'^components/com_tags',
		],
		// CLI
		'18' => ['^cli/'],
		// Administration
		'23' => ['^administrator/'],
		// Front End
		'24' => [
			'^components/',
			'^modules/',
			'^plugins/',
			'^templates/',
		],
		// Installation
		'25' => ['^installation/'],
		// Language & Strings
		'27' => [
			'^administrator/language',
			'^installation/language',
			'^language',
			'^media/system/js/fields/calendar-locales',
		],
		// Plugins
		'28' => ['^plugins/'],
		// Site Template
		'30' => ['^templates/'],
		// Admin templates
		'31' => ['^administrator/templates/'],
		// Media Manager
		'35' => [
			'^administrator/components/com_media',
			'^components/com_media',
		],
		// Repository
		'36' => [
			'^build/',
			'^.github/',
			'.gitignore',
			'README.md',
			'README.txt',
			'build.xml',
			'.gitignore',
			'.php_cs',
			'Gemfile',
			'grunt-settings.yaml',
			'grunt-readme.md',
			'Gruntfile.js',
			'scss-lint-report.xml',
			'sccs-lint.yml',
		],
		// Component com_ajax
		'41' => [
			'^administrator/components/com_ajax',
			'^components/com_ajax',
		],
		// Component com_admin
		'42' => ['^administrator/components/com_admin'],
		// Component com_associations
		'72' => ['^administrator/components/com_associations'],
		// Component com_banners
		'43' => [
			'^administrator/components/com_banners',
			'^components/com_banners',
		],
		// Component com_cache
		'44' => ['^administrator/components/com_cache'],
		// Component com_categories
		'45' => ['^administrator/components/com_categories'],
		// Component com_checkin
		'46' => ['^administrator/components/com_checkin'],
		// Component com_config
		'47' => [
			'^administrator/components/com_config',
			'^components/com_config',
		],
		// Component com_contact
		'48' => [
			'^administrator/components/com_contact',
			'^components/com_contact',
		],
		// Component com_content
		'49' => [
			'^administrator/components/com_content',
			'^components/com_content',
		],
		// Component com_contenthistory
		'50' => [
			'^administrator/components/com_contenthistory',
			'^components/com_contenthistory',
		],
		// Component com_cpanel
		'51' => ['^administrator/components/com_cpanel'],
		// Component com_finder
		'52' => [
			'^administrator/components/com_finder',
			'^components/com_finder',
		],
		// Component com_installer
		'53' => ['^administrator/components/com_installer'],
		// Component com_joomlaupdate
		'54' => ['^administrator/components/com_joomlaupdate'],
		// Component com_lanuages
		'55' => ['^administrator/components/com_languages'],
		// Component com_login
		'56' => ['^administrator/components/com_login'],
		// Component com_menus
		'58' => ['^administrator/components/com_menus'],
		// Component com_messages
		'59' => ['^administrator/components/com_messages'],
		// Component com_modules
		'60' => [
			'^administrator/components/com_modules',
			'^components/com_modules',
		],
		// Component com_newsfeeds
		'61' => [
			'^administrator/components/com_newsfeeds',
			'^components/com_newsfeeds',
		],
		// Component com_plugins
		'62' => ['^administrator/components/com_plugins'],
		// Component com_postinstall
		'63' => ['^administrator/components/com_postinstall'],
		// Component com_redirect
		'64' => ['^administrator/components/com_redirect'],
		// Component com_search
		'65' => ['^administrator/components/com_search'],
		// Component com_templates
		'67' => ['^administrator/components/com_templates'],
		// Component com_users
		'68' => [
			'^administrator/components/com_users',
			'^components/com_users',
		],
		// Component com_mailto
		'69' => ['^components/com_mailto'],
		// Component com_wrapper
		'70' => ['^components/com_wrapper'],
		// Component com_fields
		'71' => [
			'^administrator/components/com_fields',
			'^components/com_fields',
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

		/*
		 * Only perform these events if this is a new pull, action will be 'opened'
		 * Generally this isn't necessary, however if the initial create webhook fails and someone redelivers the webhook from GitHub,
		 * then this will allow the correct actions to be taken
		 */
		if ($arguments['action'] === 'opened')
		{
			// Check if the pull request targets the master branch
			$this->checkMasterBranch($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Send a message if there is no comment in the pull request
			$this->checkPullBody($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Set the status to pending
			$this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
		}

		// Only perform these events if this is a reopened pull, action will be 'reopened'
		if ($arguments['action'] === 'reopened')
		{
			// Set the status to pending
			$this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
		}

		// Only perform these events for open/close/edit/sync events
		if (in_array($arguments['action'], ['opened', 'closed', 'reopened', 'edited', 'synchronize']))
		{
			// Check that pull requests have certain labels
			$this->checkPullLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

			// Place the JoomlaCode ID in the issue title if it isn't already there
			$this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Add a RTC label if the item is in that status
			$this->checkRTClabel($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

			// Check the Categories based on the files that gets changed
			$this->checkCategories($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
		}
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

		// Get the files modified by the pull request
		$files = $this->getChangedFilesByPullRequest($hookData, $github, $logger, $project);

		if (empty($files))
		{
			// If there are no changed files return
			return;
		}

		$prLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $prLabel);

		// Add the PR label if it isn't already set
		if (!$prLabelSet)
		{
			$addLabels[] = $prLabel;
		}

		$composerChange   = $this->checkComposerChange($files);
		$composerLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $composerLabel);

		// Add the label if we change a Composer dependency and it isn't already set
		if ($composerChange && !$composerLabelSet)
		{
			$addLabels[] = $composerLabel;
		}
		// Remove the label if we don't change a Composer dependency
		elseif (!$composerChange && $composerLabelSet)
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
		elseif (!$languageChange && $languageLabelSet)
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
		elseif (!$unitSystemTestsChange && $unitSystemTestsLabelSet)
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
				// Check for file paths administrator/language, installation/language,
				// language and media/system/js/fields/calendar-locales at position 0
				if (strpos($file->filename, 'administrator/language') === 0
					|| strpos($file->filename, 'installation/language') === 0
					|| strpos($file->filename, 'language') === 0
					|| strpos($file->filename, 'media/system/js/fields/calendar-locales') === 0)
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
					|| $file->filename == '.appveyor.yml'
					|| $file->filename == '.drone.yml'
					|| $file->filename == '.travis.yml'
					|| $file->filename == 'appveyor-phpunit.xml'
					|| $file->filename == 'karma.conf.js'
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
		$currentCategories = $this->getCategories($table);

		// Hold the category ids that are added to the issue but not handled by the tracker to readd it later
		$categoriesThatShouldStay = array_diff($currentCategories, array_keys($this->trackerHandledCategories));

		// Get the files tha gets changed with this Pull Request
		$files = $this->getChangedFilesByPullRequest($hookData, $github, $logger, $project);

		if (empty($files))
		{
			// If there are no changed files do nothing here.
			return;
		}

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
	 * Check the changed files and return the correct categories if possible.
	 *
	 * @param   array  $files  The files array
	 *
	 * @return  array  IDs of categories the file set belongs to.
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
					$check = str_replace('/', '\/', $check);

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
