<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\Tracker\Tests\Hooks\Listeners;

use JTracker\Tests\Mocks\JoomlaCmsPullsListenerMock;

/**
 * Class JoomlaCmsPullsListenerCheckFilesAndAssignCategoryTest
 *
 * @since  1.0
 */
class JoomlaCmsPullsListenerCheckFilesAndAssignCategoryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var    JoomlaCmsPullsListenerMock
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
		$this->object = new JoomlaCmsPullsListenerMock;
	}

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testDirectories()
	{
		$files = [
			'test.js' => ['1'],
			'installation/' => ['25'],
			'administrator/' => ['23'],
			'administrator/language' => ['23', '27'],
			'administrator/templates/' => ['23', '31'],
			'installation/language' => ['25', '27'],
			'language' => ['27'],
			'tests' => ['14'],
			'.travis.yml' => ['14'],
			'phpunit.xml.dist' => ['14'],
			'travisci-phpunit.xml' => ['14'],
			'libraries/' => ['12'],
			'layouts/' => ['15'],
			'cli/' => ['18'],
			'libraries/fof/' => ['4', '12'],
			'libraries/idna_convert/' => ['4', '12'],
			'libraries/phpass/' => ['4', '12'],
			'libraries/phputf8/' => ['4', '12'],
			'libraries/simplepie/' => ['4', '12'],
			'libraries/vendor/' => ['4', '12'],
			'media/editors/codemirror' => ['4'],
			'media/editors/tinymce' => ['4'],
			'composer.json' => ['4'],
			'composer.lock' => ['4'],
			'build/' => ['36'],
			'.github/' => ['36'],
			'.gitignore' => ['36'],
			'CONTRIBUTING.md' => ['36'],
			'README.md' => ['36'],
			'README.txt' => ['36'],
			'build.xml' => ['36'],
			'administrator/components/com_tags' => ['16', '23', '29'],
			'components/com_tags' => ['16', '24', '29'],
			'administrator/components/com_admin/sql/updates' => ['10', '23', '29'],
			'installation/sql' => ['10', '25'],
			'administrator/components/com_admin/sql/updates/postgresql' => ['2', '10', '23', '29'],
			'installation/sql/postgresql' => ['2', '10', '25'],
			'administrator/components/com_admin/sql/updates/sqlazure' => ['3', '10', '23', '29'],
			'installation/sql/sqlazure' => ['3', '10', '25'],
			'administrator/components/com_media' => ['23', '29', '35'],
			'components/com_media' => ['24', '29', '35'],
			'components/' => ['24', '29'],
			'modules/' => ['13', '24'],
			'plugins/' => ['24', '28'],
			'templates/' => ['24', '30'],
			'administrator/components/' => ['23', '29'],
			'administrator/modules/' => ['13', '23'],
		];

		$f = new \stdClass;

		foreach ($files as $file => $catIds)
		{
			$f->filename = $file;

			$this->assertThat(
				$this->object->testCheckFilesAndAssignCategory([$f]),
				$this->equalTo($catIds),
				'Check failed for: ' . $file
			);
		}
	}

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testCheckInstallFolderAndJs()
	{
		$files = json_decode('[
			{ "filename" : "installation/" },
			{ "filename" : "test.js" }
		]');

		$this->assertThat(
			$this->object->testCheckFilesAndAssignCategory($files),
			$this->equalTo(['25', '1'])
		);
	}

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testEmpty()
	{
		$this->assertThat(
			$this->object->testCheckFilesAndAssignCategory([]),
			$this->equalTo([])
		);
	}
}
