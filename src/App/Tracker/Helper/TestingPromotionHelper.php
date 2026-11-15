<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Helper;

use App\Projects\TrackerProject;
use App\Tracker\Model\PromotionModel;
use JTracker\Mattermost\MattermostNotifier;
use Psr\Log\LoggerInterface;

/**
 * Posts pull requests to the testing channel when they get the promotion label.
 *
 * @since  1.0
 */
class TestingPromotionHelper
{
    /**
     * Number of lines of the testing instructions shown in the message
     */
    public const MAX_INSTRUCTION_LINES = 15;

    /**
     * Successful tests a pull request needs
     */
    public const TESTS_NEEDED = 2;

    /**
     * The notifier
     *
     * @var    MattermostNotifier
     * @since  1.0
     */
    private $notifier;

    /**
     * The promotion model
     *
     * @var    PromotionModel
     * @since  1.0
     */
    private $model;

    /**
     * The logger
     *
     * @var    LoggerInterface
     * @since  1.0
     */
    private $logger;

    /**
     * Constructor
     *
     * @param   MattermostNotifier  $notifier  The notifier.
     * @param   PromotionModel      $model     The promotion model.
     * @param   LoggerInterface     $logger    The logger.
     *
     * @since   1.0
     */
    public function __construct(MattermostNotifier $notifier, PromotionModel $model, LoggerInterface $logger)
    {
        $this->notifier = $notifier;
        $this->model    = $model;
        $this->logger   = $logger;
    }

    /**
     * Post a pull request to the testing channel, once per label.
     *
     * Errors are logged and never thrown, so that they don't break the webhook processing.
     *
     * @param   TrackerProject  $project  The project.
     * @param   object          $item     The pull request, with id, issue_number, title, description_raw and pr_head_sha.
     * @param   string          $label    The label that was set.
     * @param   string          $by       GitHub login of the user who set the label.
     * @param   string          $baseUrl  The base URL of the tracker, with a trailing slash.
     *
     * @return  boolean  True if the pull request was posted.
     *
     * @since   1.0
     */
    public function promote(TrackerProject $project, object $item, string $label, string $by, string $baseUrl): bool
    {
        $issueId = (int) $item->id;

        try {
            if (!$this->model->claim($issueId, $label, $by)) {
                $this->logger->info(\sprintf('Pull request #%d was posted for testing before, not posting it again.', $item->issue_number));

                return false;
            }

            $message = $this->buildMessage(
                $project,
                $item,
                $by,
                $baseUrl,
                $this->model->countSuccessfulTests($issueId, $item->pr_head_sha ?? null),
                $this->model->getTestSuitability($issueId)
            );
        } catch (\Exception $exception) {
            $this->logger->error('Error preparing the testing channel post for pull request #' . $item->issue_number, ['exception' => $exception]);

            return false;
        }

        try {
            $this->notifier->post($message);
        } catch (\Exception $exception) {
            $this->logger->error('Error posting pull request #' . $item->issue_number . ' to the testing channel', ['exception' => $exception]);

            // Allow a new attempt when the label is set again
            try {
                $this->model->release($issueId, $label);
            } catch (\Exception $releaseException) {
                $this->logger->error('Error releasing the promotion of pull request #' . $item->issue_number, ['exception' => $releaseException]);
            }

            return false;
        }

        $this->logger->info(\sprintf('Posted pull request #%d to the testing channel.', $item->issue_number));

        return true;
    }

    /**
     * Build the message for the testing channel.
     *
     * @param   TrackerProject  $project      The project.
     * @param   object          $item         The pull request.
     * @param   string          $by           GitHub login of the user who set the label.
     * @param   string          $baseUrl      The base URL of the tracker, with a trailing slash.
     * @param   integer         $tests        Number of successful tests of the current commit.
     * @param   string|null     $suitability  The test suitability rating of the AI review, if any.
     *
     * @return  string  Markdown
     *
     * @since   1.0
     */
    public static function buildMessage(
        TrackerProject $project,
        object $item,
        string $by,
        string $baseUrl,
        int $tests,
        ?string $suitability
    ): string {
        $trackerUrl = $baseUrl . 'tracker/' . $project->alias . '/' . $item->issue_number;
        $gitHubUrl  = 'https://github.com/' . $project->gh_user . '/' . $project->gh_project . '/pull/' . $item->issue_number;

        $lines   = [];
        $lines[] = \sprintf('#### Testers wanted: [#%d %s](%s)', $item->issue_number, self::escape((string) $item->title), $trackerUrl);

        $facts = [
            '[Pull request on GitHub](' . $gitHubUrl . ')',
            \sprintf('Successful tests: %d of %d', $tests, self::TESTS_NEEDED),
        ];

        $suitabilityLabels = [
            'yes'    => 'testable in the browser on a fresh install',
            'partly' => 'needs a special setup, see the instructions',
            'no'     => 'needs a special environment',
        ];

        if ($suitability !== null && isset($suitabilityLabels[$suitability])) {
            $facts[] = 'Test setup: ' . $suitabilityLabels[$suitability];
        }

        $facts[] = 'Promoted by @' . $by;
        $lines[] = implode(' · ', $facts);
        $lines[] = '';

        $instructions = DescriptionSectionsHelper::getSection((string) ($item->description_raw ?? ''), DescriptionSectionsHelper::TESTING_HEADING);

        if ($instructions === null || trim($instructions) === '') {
            $lines[] = '_The pull request has no testing instructions yet._';
        } else {
            $instructionLines = explode("\n", trim($instructions));

            $lines[] = '**Testing instructions**';

            foreach (\array_slice($instructionLines, 0, self::MAX_INSTRUCTION_LINES) as $line) {
                $lines[] = '> ' . $line;
            }

            if (\count($instructionLines) > self::MAX_INSTRUCTION_LINES) {
                $lines[] = '> …';
                $lines[] = '';
                $lines[] = '[Read the full instructions](' . $trackerUrl . ')';
            }
        }

        $lines[] = '';
        $lines[] = 'Please report your test result in the tracker: [' . $trackerUrl . '](' . $trackerUrl . ')';

        return implode("\n", $lines);
    }

    /**
     * Escape characters in a title that would break the Markdown link.
     *
     * @param   string  $text  The text.
     *
     * @return  string
     *
     * @since   1.0
     */
    private static function escape(string $text): string
    {
        return str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $text);
    }
}
