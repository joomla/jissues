<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Make extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    \Joomla\Github\Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'The make engine.';

		$this->addOption(
			new TrackerCommandOption(
				'noprogress', '',
				'Don\'t use a progress bar.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Make');

		$this->out('<error>                                    </error>');
		$this->out('<error>  Please use one of the following:  </error>');
		$this->out('<error>  make docu                         </error>');
		$this->out('<error>  make autocomplete                 </error>');
		$this->out('<error>  make dbcomments                   </error>');
		$this->out('<error>  make depfile                      </error>');
		$this->out('<error>  make langfiles                    </error>');
		$this->out('<error>  make langtemplates                </error>');
		$this->out('<error>                                    </error>');
	}
}
