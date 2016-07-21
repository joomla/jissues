<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use Elkuku\Crowdin\Crowdin;

use BabDev\Transifex\Transifex;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

use Joomla\Github\Github;

/**
 * Command package for updating selected resources
 *
 * @since  1.0
 */
class Update extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Transifex object
	 *
	 * @var    Transifex
	 * @since  1.0
	 */
	protected $transifex;

	/**
	 * Crowdin object
	 *
	 * @var    Crowdin
	 * @since  1.0
	 */
	protected $crowdin;

	/**
	 * The language provider.
	 *
	 * @var string
	 * @since  1.0
	 */
	protected $languageProvider;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = g11n3t('Used to update resources');

		$this
			->addOption(
				new TrackerCommandOption(
					'project', 'p',
					g11n3t('Process the project with the given ID.')
				)
			)
			->addOption(
				new TrackerCommandOption(
					'noprogress', '',
					g11n3t("Don't use a progress bar.")
				)
		);
	}

	/**
	 * Execute the command.
	 *
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		return $this->displayMissingOption(__DIR__);
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

		return $this;
	}

	/**
	 * Setup the Provider object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupLanguageProvider()
	{
		$this->languageProvider = $this->getOption('provider');

		switch ($this->languageProvider)
		{
			case 'transifex':
				$this->transifex = $this->getContainer()->get('transifex');
				break;

			case 'crowdin':
				$this->crowdin = $this->getContainer()->get('crowdin');
				break;

			default:
				throw new \UnexpectedValueException('Unknown language provider');
				break;
		}

		return $this;
	}
}
