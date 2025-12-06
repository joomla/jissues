<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks\Listeners;

use App\Tracker\Table\IssuesTable;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Github\Github;
use Joomla\Http\Exception\InvalidResponseCodeException;
use Monolog\Logger;

/**
 * Event listener for the joomla-cms pull request hook
 *
 * @since  1.0
 */
class JoomlacmsPullsListener extends AbstractListener implements SubscriberInterface
{
    public const CATEGORY_JAVASCRIPT         = 1;
    public const CATEGORY_POSTGRESQL         = 2;
    public const CATEGORY_SQLSERVER          = 3;
    public const CATEGORY_EXTERNAL_LIBRARY   = 4;
    public const CATEGORY_SQL                = 10;
    public const CATEGORY_LIBRARIES          = 12;
    public const CATEGORY_MODULES            = 13;
    public const CATEGORY_UNIT_TESTS         = 14;
    public const CATEGORY_LAYOUTS            = 15;
    public const CATEGORY_TAGS               = 16;
    public const CATEGORY_CLI                = 18;
    public const CATEGORY_ADMINISTRATION     = 23;
    public const CATEGORY_FRONTEND           = 24;
    public const CATEGORY_INSTALLATION       = 25;
    public const CATEGORY_LANGUAGES          = 27;
    public const CATEGORY_PLUGINS            = 28;
    public const CATEGORY_SITE_TEMPLATES     = 30;
    public const CATEGORY_ADMIN_TEMPLATES    = 31;
    public const CATEGORY_MEDIA_MANAGER      = 35;
    public const CATEGORY_REPOSITORY         = 36;
    public const CATEGORY_COM_AJAX           = 41;
    public const CATEGORY_COM_ADMIN          = 42;
    public const CATEGORY_COM_BANNERS        = 43;
    public const CATEGORY_COM_CACHE          = 44;
    public const CATEGORY_COM_CATEGORIES     = 45;
    public const CATEGORY_COM_CHECKIN        = 46;
    public const CATEGORY_COM_CONFIG         = 47;
    public const CATEGORY_COM_CONTACT        = 48;
    public const CATEGORY_COM_CONTENT        = 49;
    public const CATEGORY_COM_CONTENTHISTORY = 50;
    public const CATEGORY_COM_CPANEL         = 51;
    public const CATEGORY_COM_FINDER         = 52;
    public const CATEGORY_COM_INSTALLER      = 53;
    public const CATEGORY_COM_JOOMLAUPDATE   = 54;
    public const CATEGORY_COM_LANGUAGES      = 55;
    public const CATEGORY_COM_LOGIN          = 56;
    public const CATEGORY_COM_MENUS          = 58;
    public const CATEGORY_COM_MESSAGES       = 59;
    public const CATEGORY_COM_MODULES        = 60;
    public const CATEGORY_COM_NEWSFEEDS      = 61;
    public const CATEGORY_COM_PLUGINS        = 62;
    public const CATEGORY_COM_POSTINSTALL    = 63;
    public const CATEGORY_COM_REDIRECT       = 64;
    public const CATEGORY_COM_SEARCH         = 65;
    public const CATEGORY_COM_TEMPLATES      = 67;
    public const CATEGORY_COM_USERS          = 68;
    public const CATEGORY_COM_MAILTO         = 69;
    public const CATEGORY_COM_WRAPPER        = 70;
    public const CATEGORY_COM_FIELDS         = 71;
    public const CATEGORY_COM_ASSOCIATIONS   = 72;
    public const CATEGORY_COMPOSER           = 73;
    public const CATEGORY_COM_CSP            = 74;
    public const CATEGORY_COM_WORKFLOW       = 75;
    public const CATEGORY_NPM                = 76;

    /**
     * The Tracker Categories that are handled based on the files that changed by a pull request.
     *
     * The category index is provided as the key while the values are containing regular expressions matching the file paths.
     *
     * @var    array
     * @since  1.0
     */
    protected $trackerHandledCategories = [
        self::CATEGORY_JAVASCRIPT => [
            '.js$',
        ],
        self::CATEGORY_POSTGRESQL => [
            '^administrator/components/com_admin/sql/updates/postgresql',
            '^installation/sql/postgresql',
            '^libraries/joomla/database/(.*)/postgresql.php',
        ],
        self::CATEGORY_SQLSERVER => [
            '^administrator/components/com_admin/sql/updates/sqlazure',
            '^installation/sql/sqlazure',
            '^libraries/joomla/database/(.*)/sqlazure.php',
            '^libraries/joomla/database/(.*)/sqlsrv.php',
        ],
        self::CATEGORY_EXTERNAL_LIBRARY => [
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
        self::CATEGORY_SQL => [
            '^administrator/components/com_admin/sql/updates',
            '^installation/sql',
        ],
        self::CATEGORY_LIBRARIES => [
            '^libraries/',
        ],
        self::CATEGORY_MODULES => [
            '^administrator/modules/',
            '^modules/',
        ],
        self::CATEGORY_UNIT_TESTS => [
            '^tests',
            '.travis.yml',
            'phpunit.xml.dist',
            'travisci-phpunit.xml',
            'appveyor-phpunit.xml',
            '.appveyor.yml',
            '.drone.yml',
            'karma.conf.js',
            'codeception.yml',
            'Jenkinsfile',
            'jenkins-phpunit.xml',
            'RoboFile.php',
            'RoboFile.dist.ini',
            'drone-package.json',
            '.hound.yml',
        ],
        self::CATEGORY_LAYOUTS => [
            '^layouts/',
        ],
        self::CATEGORY_TAGS => [
            '^administrator/components/com_tags',
            '^components/com_tags',
        ],
        self::CATEGORY_CLI => [
            '^cli/',
        ],
        self::CATEGORY_ADMINISTRATION => [
            '^administrator/',
        ],
        self::CATEGORY_FRONTEND => [
            '^components/',
            '^modules/',
            '^plugins/',
            '^templates/',
        ],
        self::CATEGORY_INSTALLATION => [
            '^installation/',
        ],
        self::CATEGORY_LANGUAGES => [
            '^administrator/language',
            '^api/language',
            '^installation/language',
            '^language',
            '^media/system/js/fields/calendar-locales',
            '^build/media_source/system/js/fields/calendar-locales',
            '^build/media_source/vendor/tinymce/langs',
            '^administrator/templates/atum/language',
            '^administrator/templates/isis/language',
            '^administrator/templates/hathor/language',
            '^templates/protostar/language',
            '^templates/beez3/language',
        ],
        self::CATEGORY_PLUGINS => [
            '^plugins/',
        ],
        self::CATEGORY_SITE_TEMPLATES => [
            '^templates/',
        ],
        self::CATEGORY_ADMIN_TEMPLATES => [
            '^administrator/templates/',
        ],
        self::CATEGORY_MEDIA_MANAGER => [
            '^administrator/components/com_media',
            '^components/com_media',
        ],
        self::CATEGORY_REPOSITORY => [
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
        self::CATEGORY_COM_AJAX => [
            '^administrator/components/com_ajax',
            '^components/com_ajax',
        ],
        self::CATEGORY_COM_ADMIN => [
            '^administrator/components/com_admin',
        ],
        self::CATEGORY_COM_BANNERS => [
            '^administrator/components/com_banners',
            '^components/com_banners',
        ],
        self::CATEGORY_COM_CACHE => [
            '^administrator/components/com_cache',
        ],
        self::CATEGORY_COM_CATEGORIES => [
            '^administrator/components/com_categories',
        ],
        self::CATEGORY_COM_CHECKIN => [
            '^administrator/components/com_checkin',
        ],
        self::CATEGORY_COM_CONFIG => [
            '^administrator/components/com_config',
            '^components/com_config',
        ],
        self::CATEGORY_COM_CONTACT => [
            '^administrator/components/com_contact',
            '^components/com_contact',
        ],
        self::CATEGORY_COM_CONTENT => [
            '^administrator/components/com_content',
            '^components/com_content',
        ],
        self::CATEGORY_COM_CONTENTHISTORY => [
            '^administrator/components/com_contenthistory',
            '^components/com_contenthistory',
        ],
        self::CATEGORY_COM_CPANEL => [
            '^administrator/components/com_cpanel',
        ],
        self::CATEGORY_COM_FINDER => [
            '^administrator/components/com_finder',
            '^components/com_finder',
        ],
        self::CATEGORY_COM_INSTALLER => [
            '^administrator/components/com_installer',
        ],
        self::CATEGORY_COM_JOOMLAUPDATE => [
            '^administrator/components/com_joomlaupdate',
        ],
        self::CATEGORY_COM_LANGUAGES => [
            '^administrator/components/com_languages',
        ],
        self::CATEGORY_COM_LOGIN => [
            '^administrator/components/com_login',
        ],
        self::CATEGORY_COM_MENUS => [
            '^administrator/components/com_menus',
        ],
        self::CATEGORY_COM_MESSAGES => [
            '^administrator/components/com_messages',
        ],
        self::CATEGORY_COM_MODULES => [
            '^administrator/components/com_modules',
            '^components/com_modules',
        ],
        self::CATEGORY_COM_NEWSFEEDS => [
            '^administrator/components/com_newsfeeds',
            '^components/com_newsfeeds',
        ],
        self::CATEGORY_COM_PLUGINS => [
            '^administrator/components/com_plugins',
        ],
        self::CATEGORY_COM_POSTINSTALL => [
            '^administrator/components/com_postinstall',
        ],
        self::CATEGORY_COM_REDIRECT => [
            '^administrator/components/com_redirect',
        ],
        self::CATEGORY_COM_SEARCH => [
            '^administrator/components/com_search',
            '^components/com_search',
        ],
        self::CATEGORY_COM_TEMPLATES => [
            '^administrator/components/com_templates',
        ],
        self::CATEGORY_COM_USERS => [
            '^administrator/components/com_users',
            '^components/com_users',
        ],
        self::CATEGORY_COM_MAILTO => [
            '^components/com_mailto',
        ],
        self::CATEGORY_COM_WRAPPER => [
            '^components/com_wrapper',
        ],
        self::CATEGORY_COM_FIELDS => [
            '^administrator/components/com_fields',
            '^components/com_fields',
        ],
        self::CATEGORY_COM_ASSOCIATIONS => [
            '^administrator/components/com_associations',
        ],
        self::CATEGORY_COMPOSER => [
            '^libraries/vendor',
            'composer.json',
            'composer.lock',
        ],
        self::CATEGORY_COM_CSP => [
            '^administrator/components/com_csp',
            '^components/com_csp',
        ],
        self::CATEGORY_COM_WORKFLOW => [
            '^administrator/components/com_workflow',
        ],
        self::CATEGORY_NPM => [
            '\.scss$',
            '^administrator/components/com_media/resources/scripts',
            '^administrator/components/com_media/resources/styles',
            'administrator/components/com_media/package-lock.json',
            'administrator/components/com_media/package.json',
            'administrator/components/com_media/webpack.config.js',
            '^build/media_source',
            'build.js',
            'package-lock.json',
            'package.json',
        ],
    ];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPullAfterCreate' => 'onPullAfterCreate',
            'onPullAfterUpdate' => 'onPullAfterUpdate',
        ];
    }

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
        if ($arguments['action'] === 'opened') {
            // Check that pull requests have certain labels
            $this->checkPullLabels($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

            // Place the JoomlaCode ID in the issue title if it isn't already there
            $this->updatePullTitle($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);

            // Send a message if there is no comment in the pull request
            $this->checkPullBody($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

            // Set the status to pending
            $this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);

            // Check the Categories based on the files that gets changed
            $this->checkCategories($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project'], $arguments['table']);
        }

        // Detect first time contributiors...
        if ($arguments['hookData']->pull_request->author_association === 'FIRST_TIME_CONTRIBUTOR' || $arguments['hookData']->pull_request->author_association === 'FIRST_TIMER') {
            // ... and send the message
            $this->sendFirstTimeContributorMessage($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);
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
        if ($arguments['action'] === 'opened') {
            // Send a message if there is no comment in the pull request
            $this->checkPullBody($arguments['hookData'], $arguments['github'], $arguments['logger'], $arguments['project']);

            // Set the status to pending
            $this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
        }

        // Only perform these events if this is a reopened pull, action will be 'reopened'
        if ($arguments['action'] === 'reopened') {
            // Set the status to pending
            $this->setPending($arguments['logger'], $arguments['project'], $arguments['table']);
        }

        // Only perform these events for open/close/edit/sync events
        if (\in_array($arguments['action'], ['opened', 'closed', 'reopened', 'edited', 'synchronize'])) {
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
        if ($labelIsSet === true && $table->status != 4) {
            // Remove the RTC label as it isn't longer set to RTC
            $labels[] = $label;
            $this->removeLabels($hookData, $github, $logger, $project, $labels);
        }

        if ($labelIsSet === false && $table->status == 4) {
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
        $rfcLabel             = 'RFC';
        $languageLabel        = 'Language Change';
        $unitSystemTestsLabel = 'Unit/System Tests';
        $composerLabel        = 'Composer Dependency Changed';
        $npmLabel             = 'NPM Resource Changed';
        $addLabels            = [];
        $removeLabels         = [];

        $rfcIssue    = strpos($hookData->pull_request->title, '[RFC]') !== false;
        $rfcLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $rfcLabel);

        // Add the label if we have a RFC issue
        if ($rfcIssue && !$rfcLabelSet) {
            $addLabels[] = $rfcLabel;
        } elseif (!$rfcIssue && $rfcLabelSet) {
            // Remove the label if we don't have a RFC issue
            $removeLabels[] = $rfcLabel;
        }

        // Get the files modified by the pull request
        $files = $this->getChangedFilesByPullRequest($hookData, $github, $logger, $project);

        if (empty($files)) {
            // If there are no changed files return
            return;
        }

        $prLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $prLabel);

        // Add the PR label if it isn't already set
        if (!$prLabelSet) {
            $addLabels[] = $prLabel;
        }

        // Check other labels
        $labelCategoryMap = [
            $languageLabel        => self::CATEGORY_LANGUAGES,
            $unitSystemTestsLabel => self::CATEGORY_UNIT_TESTS,
            $composerLabel        => self::CATEGORY_COMPOSER,
            $npmLabel             => self::CATEGORY_NPM,
        ];

        foreach ($labelCategoryMap as $label => $category) {
            $hasChangeForCategory = $this->checkChange($files, $category);
            $hasLabel             = $this->checkLabel($hookData, $github, $logger, $project, $label);

            // Ensure the label is set if a file matches the requirements
            if ($hasChangeForCategory && !$hasLabel) {
                $addLabels[] = $label;
            } elseif (!$hasChangeForCategory && $hasLabel) {
                // Remove the label if no file matches the requirements
                $removeLabels[] = $label;
            }
        }

        // Add the labels if we need
        if (!empty($addLabels)) {
            $this->addLabels($hookData, $github, $logger, $project, $addLabels);
        }

        // Remove the labels if we need
        if (!empty($removeLabels)) {
            $this->removeLabels($hookData, $github, $logger, $project, $removeLabels);
        }
    }

    /**
     * Check if we change a file matching the passed category id.
     *
     * @param   array    $files  The files array
     * @param   integer  $id     The id of the category we should check
     *
     * @return  boolean   True if we change a file matching the passed category id.
     *
     * @since   1.0
     */
    protected function checkChange($files, $id)
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                foreach ($this->trackerHandledCategories[$id] as $check) {
                    $check = str_replace('/', '\/', $check);

                    if (preg_match('/' . $check . '/', $file->filename)) {
                        return true;
                    }
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
        if ($table->status == 3) {
            return;
        }

        // Reset the issue status to pending and try updating the database
        try {
            $table->save(['status' => 3]);
        } catch (\InvalidArgumentException $e) {
            $logger->error(
                \sprintf(
                    'Error setting the status to pending in local application for GitHub pull request %s/%s #%d',
                    $project->gh_user,
                    $project->gh_project,
                    $table->issue_number
                ),
                ['exception' => $e]
            );
        } catch (\RuntimeException $e) {
            $logger->error(
                \sprintf(
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
        if (preg_match('/\[#([0-9]+)\]/', $hookData->pull_request->title, $matches)) {
            return;
        }

        // If we don't have a foreign ID, we can't do anything here
        if ($table->foreign_number === null) {
            return;
        }

        // Define the new title
        $title = '[#' . $table->foreign_number . '] ' . $table->title;

        try {
            $github->pulls->edit(
                $project->gh_user,
                $project->gh_project,
                $hookData->pull_request->number,
                $title
            );

            // Post the new label on the object
            $logger->info(
                \sprintf(
                    'Updated the title for GitHub pull request %s/%s #%d',
                    $project->gh_user,
                    $project->gh_project,
                    $hookData->pull_request->number
                )
            );
        } catch (InvalidResponseCodeException $e) {
            $logger->error(
                \sprintf(
                    'Error updating the title for GitHub pull request %s/%s #%d',
                    $project->gh_user,
                    $project->gh_project,
                    $hookData->pull_request->number
                ),
                ['exception' => $e]
            );

            // Don't change the title locally
            return;
        } catch (\DomainException $e) {
            $logger->error(
                \sprintf(
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
        try {
            $data = ['title' => $title];
            $table->save($data);
        } catch (\Exception $e) {
            $logger->error(
                \sprintf(
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
        if ($hookData->pull_request->body == '') {
            // Post a comment on the PR asking to add a description
            try {
                $addLabels                       = [];
                $testInstructionsMissingLabel    = 'Test instructions missing';
                $testInstructionsMissingLabelSet = $this->checkLabel($hookData, $github, $logger, $project, $testInstructionsMissingLabel);

                // Add the Test instructions missing label if it isn't already set
                if (!$testInstructionsMissingLabelSet) {
                    $addLabels[] = $testInstructionsMissingLabel;
                    $this->addLabels($hookData, $github, $logger, $project, $addLabels);
                }

                $appNote = \sprintf(
                    '<br />*This is an automated message from the <a href="%1$s">%2$s Application</a>.*',
                    'https://github.com/joomla/jissues',
                    'J!Tracker'
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
                    \sprintf(
                        'Added a no description comment to %s/%s #%d',
                        $project->gh_user,
                        $project->gh_project,
                        $hookData->pull_request->number
                    )
                );
            } catch (InvalidResponseCodeException $e) {
                $logger->error(
                    \sprintf(
                        'Error posting comment to GitHub pull request %s/%s #%d',
                        $project->gh_user,
                        $project->gh_project,
                        $hookData->pull_request->number
                    ),
                    ['exception' => $e]
                );
            } catch (\DomainException $e) {
                $logger->error(
                    \sprintf(
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

        if (empty($files)) {
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

        if (empty($files)) {
            // Nothing to do here...
            return [];
        }

        foreach ($files as $file) {
            foreach ($this->trackerHandledCategories as $catIndex => $checks) {
                if (\in_array($catIndex, $categories)) {
                    continue;
                }

                foreach ($checks as $check) {
                    $check = str_replace('/', '\/', $check);

                    if (preg_match('/' . $check . '/', $file->filename)) {
                        $categories[] = $catIndex;

                        continue 2;
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Sends the first time contributor message
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
    protected function sendFirstTimeContributorMessage($hookData, Github $github, Logger $logger, $project)
    {
        $message = 'Thank you so much for spending time creating your first pull request for Joomla! We really appreciate the time you spent creating it'
            . ' and hope that this will be the first of many.<br><br>It will now be reviewed and tested so please make sure you keep an eye on your email'
            . ' for any notifications about its progress. For example there might be some code style changes requested.<br><br>More details'
            . ' <a target="_blank" href="https://docs.joomla.org/Using_the_Github_UI_to_Make_Pull_Requests#And_now.3F">in our documentation</a>.';

        $this->sendCommentAsBotUser($hookData, $github, $logger, $project, $message);
    }
}
