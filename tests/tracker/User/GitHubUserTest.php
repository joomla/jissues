<?php
/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Authentication\GitHub;

use App\Projects\TrackerProject;

use Joomla\Database\Mysqli\MysqliDriver;

use JTracker\Authentication\GitHub\GitHubUser;

/**
 * Class GitHubUserTest.
 *
 * @since  1.0
 */
class GitHubUserTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var GitHubUser
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
		$options = array();
		$driver  = new MysqliDriver($options);
		$project = new TrackerProject($driver);

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
