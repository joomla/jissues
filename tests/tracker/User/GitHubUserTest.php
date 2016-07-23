<?php
/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Authentication\GitHub;

use App\Projects\TrackerProject;

use Joomla\Database\DatabaseDriver;

use JTracker\Authentication\GitHub\GitHubUser;

/**
 * Class GitHubUserTest.
 *
 * @since  1.0
 */
class GitHubUserTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Array of global variables to blacklist when the global state snapshot is taken
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $backupGlobalsBlacklist = ['container'];

	/**
	 * @var    GitHubUser
	 * @since  1.0
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since  1.0
	 * @return void
	 */
	protected function setUp()
	{
		// Mock the base database driver without calling the original constructor
		$driver = $this->getMockForAbstractClass(DatabaseDriver::class, [], '', false);

		// Mock the project object
		$project = $this->getMockBuilder(TrackerProject::class)
			->setConstructorArgs([$driver])
			->getMock();

		$this->object = new GitHubUser($project, $driver);
	}

	/**
	 * Test loadGitHubData.
	 *
	 * @since  1.0
	 * @return void
	 */
	public function testLoadGitHubData()
	{
		$ghData = new \stdClass;

		$ghData->login      = 'elkuku';
		$ghData->avatar_url = 'http://my_avatar.png';
		$ghData->name       = 'elkuku';
		$ghData->email      = 'email@example.com';

		$this->object->loadGitHubData($ghData);

		$this->assertThat(
			$this->object->username,
			$this->equalTo('elkuku')
		);

		$this->assertThat(
			$this->object->name,
			$this->equalTo('elkuku')
		);

		$this->assertThat(
			$this->object->email,
			$this->equalTo('email@example.com')
		);
	}

	/**
	 * Test loadGitHubData.
	 *
	 * @expectedException \RuntimeException
	 *
	 * @since  1.0
	 * @return void
	 */
	public function testLoadGitHubDataFailure()
	{
		$ghData = new \stdClass;

		// Missing login !

		$this->object->loadGitHubData($ghData);
	}
}
