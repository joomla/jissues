<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application;

use App\Projects\TrackerProject;

use Elkuku\Console\Helper\ConsoleProgressBar;

use Joomla\Console\Application as FrameworkApplication;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Registry\Registry;

use JTracker\Authentication\GitHub\GitHubUser;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class Application extends FrameworkApplication implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Progress bar format.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pBarFormat = '[%bar%] %fraction% %elapsed% ETA: %estimate%';

	/**
	 * Class constructor.
	 *
	 * @param   InputInterface   $input   An optional argument to provide dependency injection for the application's input object.  If the argument is
	 *                                    an InputInterface object that object will become the application's input object, otherwise a default input
	 *                                    object is created.
	 * @param   OutputInterface  $output  An optional argument to provide dependency injection for the application's output object.  If the argument
	 *                                    is an OutputInterface object that object will become the application's output object, otherwise a default
	 *                                    output object is created.
	 * @param   Registry         $config  An optional argument to provide dependency injection for the application's config object.  If the argument
	 *                                    is a Registry object that object will become the application's config object, otherwise a default config
	 *                                    object is created.
	 *
	 * @since   2.0.0
	 */
	public function __construct(?InputInterface $input = null, ?OutputInterface $output = null, ?Registry $config = null)
	{
		// Close the application if we are not executed from the command line.
		if (!\defined('STDOUT') || !\defined('STDIN') || !isset($_SERVER['argv']))
		{
			$this->close();
		}

		// Call the constructor as late as possible (it runs `initialise`).
		parent::__construct($input, $output, $config);

		$this->usePBar = $this->get('cli-application.progress-bar');
	}

	/**
	 * Configures the console input and output instances for the process.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function configureIO(): void
	{
		if ($this->getConsoleInput()->hasParameterOption(['--nocolors'], true))
		{
			trigger_deprecation('The --nocolors flag is deprecated in favour of the --ansii and --no-ansii flags');
			$this->getConsoleOutput()->setDecorated(false);
		}

		// TODO: This probably won't work as a parameter option?
		if ($this->getConsoleInput()->hasParameterOption(['noprogress'], true))
		{
			$this->usePBar = false;
		}

		$composerCfg = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
		$this->setName('Joomla! Tracker CLI Application ' . $composerCfg->version);
	}

	/**
	 * Builds the default input definition.
	 *
	 * @return  InputDefinition
	 *
	 * @since   2.0.0
	 */
	protected function getDefaultInputDefinition(): InputDefinition
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(
			new InputOption('--nocolor', '', InputOption::VALUE_NONE, 'Suppress ANSI colours on unsupported terminals (deprecated)')
		);
		$definition->addOption(
			new InputOption('--log', '', InputOption::VALUE_NONE, 'Optionally log output to the specified log file')
		);

		return $definition;
	}

	/**
	 * Method to run the application routines.
	 *
	 * @return  integer  The exit code for the application
	 *
	 * @since   2.0.0
	 * @throws  \Throwable
	 */
	protected function doExecute(): int
	{
		$result = parent::doExecute();

		$input  = $this->getConsoleInput();
		$output = $this->getConsoleOutput();

		$outputStyle = (new SymfonyStyle($input, $output));
		$outputStyle->newLine();
		$outputStyle->text(str_repeat('_', 40));
		$outputStyle->text(
			sprintf(
				'Execution time: <b>%d sec.</b>',
				time() - $this->get('execution.timestamp')
			)
		);
		$outputStyle->text(str_repeat('_', 40));

		return $result;
	}

	/**
	 * Get the commands which should be registered by default to the application.
	 *
	 * @return  AbstractCommand[]
	 *
	 * @since   1.0.0
	 */
	protected function getDefaultCommands(): array
	{
		$defaultCommands = parent::getDefaultCommands();

		return array_merge(
			$defaultCommands,
			[
				new Command\Clear\Allcache,
				new Command\Clear\Cache,
				new Command\Clear\Twig,
				new Command\Database\Migrate,
				new Command\Database\Status,
				new Command\Get\Avatars,
				new Command\Get\Composertags,
				new Command\Get\Users,
				new Command\Install\Install,
				new Command\Make\Autocomplete,
				new Command\Make\Composergraph,
				new Command\Make\Dbcomments,
				new Command\Make\Depfile,
				new Command\Make\Docu,
				new Command\Make\Repoinfo,
			]
		);
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string   $title     The title to display.
	 * @param   string   $subTitle  A subtitle.
	 * @param   integer  $width     Total width in chars.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function outputTitle($title, $subTitle = '', $width = 60)
	{
		$this->out(str_repeat('-', $width));

		$this->out(str_repeat(' ', $width / 2 - (\strlen($title) / 2)) . '<title>' . $title . '</title>');

		if ($subTitle)
		{
			$this->out(str_repeat(' ', $width / 2 - (\strlen($subTitle) / 2)) . '<b>' . $subTitle . '</b>');
		}

		$this->out(str_repeat('-', $width));

		return $this;
	}

	/**
	 * Get a user object.
	 *
	 * Some methods check for an authenticated user...
	 *
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 */
	public function getUser()
	{
		// Urgh..
		$user = new GitHubUser(
			new TrackerProject($this->container->get('db')),
			$this->container->get('db')
		);
		$user->isAdmin = true;

		return $user;
	}

	/**
	 * Display the GitHub rate limit.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function displayGitHubRateLimit()
	{
		$this->out()
			->out('<info>GitHub rate limit:...</info> ', false);

		$rate = $this->container->get('gitHub')->authorization->getRateLimit()->resources->core;

		$this->out(sprintf('%1$d (remaining: <b>%2$d</b>)', $rate->limit, $rate->remaining))
			->out();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	public function getProgressBar($targetNum)
	{
		return ($this->usePBar)
			? new ConsoleProgressBar($this->pBarFormat, '=>', ' ', 60, $targetNum)
			: null;
	}

	/**
	 * This is a useless legacy function.
	 *
	 * Actually it's accessed by the \JTracker\Model\AbstractTrackerListModel
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @todo    Remove
	 */
	public function getUserStateFromRequest()
	{
		return '';
	}

	/**
	 * Add a profiler mark. This is being called in the stack, stopping cli updates. This bodges a fix
	 * but should be either removed or fully implemented in the future
	 *
	 * @param   string  $text  The message for the mark.
	 *
	 * @return  static  Method allows chaining
	 *
	 * @since   1.0
	 * @todo    Remove
	 */
	public function mark($text)
	{
		return $this;
	}
}
