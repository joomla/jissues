<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get;

use App\Projects\TrackerProject;
use Joomla\Http\Exception\UnexpectedResponseException;
use JTracker\Command\TrackerCommand;
use JTracker\Github\Github;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class for retrieving data from external providers for selected projects
 *
 * @since  1.0
 */
abstract class Get extends TrackerCommand
{
    /**
     * Joomla! Github object
     *
     * @var    Github
     * @since  1.0
     */
    protected $github;

    /**
     * The id of the current bot.
     *
     * @var    integer
     * @since  1.0
     */
    protected $botId = 0;

    /**
     * Project object.
     *
     * @var    TrackerProject
     * @since  1.0
     */
    protected $project;

    /**
     * Common Option for project filtering.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function addProjectOption(): void
    {
        $this->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'Process the project with the given ID.');
    }

    /**
     * Common option for progress bars.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function addProgressBarOption(): void
    {
        $this->addOption('noprogress', '', InputOption::VALUE_NONE, 'Don\'t use a progress bar.');
    }

    /**
     * Setup the Github object.
     *
     * @return  $this
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function setupGitHub()
    {
        $this->github = $this->getContainer()->get('gitHub');

        // Check the rate limit immediately and switch if need be
        $this->checkGitHubRateLimit($this->getGitHubRateLimitRemaining());

        return $this;
    }

    /**
     * Check the remaining GitHub rate limit.
     *
     * If the current account runs low, the other configured accounts are checked one after the other and the first
     * one with enough remaining requests is used. If all accounts run low, the account with the most remaining
     * requests is used, and an exception is thrown if even that one is below the minimum.
     *
     * @param   integer  $remaining  The remaining count of the current account.
     *
     * @throws \RuntimeException
     * @return $this
     *
     * @since   1.0
     */
    protected function checkGitHubRateLimit($remaining)
    {
        // @todo hard coded values..
        $minSwitch = 500;
        $minRemain = 10;

        $this->debugOut(\sprintf('Limit check: %1$d -- %2$d / %3$d', $remaining, $minSwitch, $minRemain));

        if ($remaining > $minSwitch) {
            return $this;
        }

        $accounts = $this->github->getOption('api.accounts') ?: [];

        // The remaining requests of the checked accounts, indexed by the account id
        $checked = [$this->botId => (int) $remaining];

        while (\count($checked) < \count($accounts)) {
            $this->switchGitHubAccount();

            $checked[$this->botId] = $this->getGitHubRateLimitRemaining();

            $this->debugOut(\sprintf('Limit check for %1$s: %2$d', $this->getGitHubAccountName($this->botId), $checked[$this->botId]));

            if ($checked[$this->botId] > $minSwitch) {
                return $this;
            }
        }

        // All accounts are running low, continue with the one which has the most requests left
        $bestId = array_search(max($checked), $checked, true);

        if ($bestId !== $this->botId) {
            $this->useGitHubAccount($bestId);
        }

        if ($checked[$bestId] <= $minRemain) {
            $status = [];

            foreach ($checked as $botId => $botRemaining) {
                $status[] = \sprintf('%1$s: %2$d', $this->getGitHubAccountName($botId), $botRemaining);
            }

            throw new \RuntimeException(
                \sprintf(
                    'GitHub remaining rate limit dropped below the minimum (%1$d) for all accounts (%2$s).',
                    $minRemain,
                    implode(', ', $status)
                )
            );
        }

        return $this;
    }

    /**
     * Get the remaining GitHub rate limit of the current account.
     *
     * An account whose credentials are rejected by GitHub is reported with no remaining requests.
     *
     * @return  integer
     *
     * @since   1.0
     */
    protected function getGitHubRateLimitRemaining(): int
    {
        try {
            $rate = $this->github->authorization->getRateLimit();
        } catch (UnexpectedResponseException $exception) {
            $this->logOut(
                \sprintf(
                    'Checking the rate limit of the GitHub account %1$s failed: %2$s',
                    $this->getGitHubAccountName($this->botId),
                    $exception->getMessage()
                )
            );

            return 0;
        }

        // GitHub Enterprise sites and trusted users have no rate limit
        if (!isset($rate->resources->core)) {
            return PHP_INT_MAX;
        }

        return (int) $rate->resources->core->remaining;
    }

    /**
     * Cycle through a list of GitHub accounts for "long running processes".
     *
     * @return $this
     *
     * @since   1.0
     * @throws \UnexpectedValueException
     */
    public function switchGitHubAccount()
    {
        $accounts = $this->github->getOption('api.accounts');

        if (!$accounts) {
            return $this;

            // @todo throw new \UnexpectedValueException('No GitHub accounts set in config.');
        }

        // Increase or reset the bot id counter.
        return $this->useGitHubAccount(($this->botId + 1 >= \count($accounts)) ? 0 : $this->botId + 1);
    }

    /**
     * Use the GitHub account with the given id from the list of configured accounts.
     *
     * @param   integer  $botId  The account id.
     *
     * @return $this
     *
     * @since   1.0
     */
    protected function useGitHubAccount(int $botId)
    {
        $accounts = $this->github->getOption('api.accounts');

        $this->botId = $botId;

        $username = $accounts[$this->botId]->username;
        $password = $accounts[$this->botId]->password;

        $this->github->setOption('api.username', $username);
        $this->github->setOption('api.password', $password);

        $this->logOut(\sprintf('Switched to bot account %s (%d)', $username, $this->botId));

        return $this;
    }

    /**
     * Get the name of a GitHub account for messages.
     *
     * @param   integer  $botId  The account id.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getGitHubAccountName(int $botId): string
    {
        $accounts = $this->github->getOption('api.accounts');

        if ($accounts) {
            return $accounts[$botId]->username ?? ('account ' . $botId);
        }

        return $this->github->getOption('api.username') ?: 'anonymous';
    }
}
