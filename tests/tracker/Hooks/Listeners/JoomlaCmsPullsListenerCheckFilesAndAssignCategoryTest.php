<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\Tracker\Tests\Hooks\Listeners;

use JTracker\Tests\Mocks\JoomlaCmsPullsListenerMock;
use PHPUnit\Framework\TestCase;

/**
 * Class JoomlaCmsPullsListenerCheckFilesAndAssignCategoryTest
 *
 * @since  1.0
 */
class JoomlaCmsPullsListenerCheckFilesAndAssignCategoryTest extends TestCase
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
			'administrator/' => ['23'],
			'administrator/components/' => ['23'],
			'administrator/components/com_admin/sql/updates' => ['10', '23', '42'],
			'administrator/components/com_admin/sql/updates/postgresql' => ['2', '10', '23', '42'],
			'administrator/components/com_admin/sql/updates/sqlazure' => ['3', '10', '23', '42'],
			'administrator/components/com_associations' => ['23', '72'],
			'administrator/components/com_media' => ['23', '35'],
			'administrator/components/com_tags' => ['16', '23'],
			'administrator/language' => ['23', '27'],
			'administrator/modules/' => ['13', '23'],
			'administrator/templates/' => ['23', '31'],
			'build/' => ['36'],
			'build.xml' => ['36'],
			'cli/' => ['18'],
			'components/' => ['24'],
			'components/com_media' => ['24', '35'],
			'components/com_tags' => ['16', '24'],
			'composer.json' => ['4'],
			'composer.lock' => ['4'],
			'installation/' => ['25'],
			'installation/language' => ['25', '27'],
			'installation/sql' => ['10', '25'],
			'installation/sql/postgresql' => ['2', '10', '25'],
			'installation/sql/sqlazure' => ['3', '10', '25'],
			'layouts/' => ['15'],
			'language' => ['27'],
			'libraries/' => ['12'],
			'libraries/fof/' => ['4', '12'],
			'libraries/joomla/database/query/postgresql.php' => ['2', '12'],
			'libraries/joomla/database/driver/sqlsrv.php' => ['3', '12'],
			'libraries/idna_convert/' => ['4', '12'],
			'libraries/phpass/' => ['4', '12'],
			'libraries/phputf8/' => ['4', '12'],
			'libraries/simplepie/' => ['4', '12'],
			'libraries/vendor/' => ['4', '12'],
			'media/editors/codemirror' => ['4'],
			'media/editors/tinymce' => ['4'],
			'media/system/js/fields/calendar-locales/el.js' => ['1', '27'],
			'modules/' => ['13', '24'],
			'phpunit.xml.dist' => ['14'],
			'plugins/' => ['24', '28'],
			'README.md' => ['36'],
			'README.txt' => ['36'],
			'templates/' => ['24', '30'],
			'test.js' => ['1'],
			'tests' => ['14'],
			'travisci-phpunit.xml' => ['14'],
			'.travis.yml' => ['14'],
			'.github/' => ['36'],
			'.gitignore' => ['36'],
			'.appveyor.yml' => ['14'],
			'.drone.yml' => ['14'],
			'.gitignore' => ['36'],
			'.php_cs' => ['36'],
			'.travis.yml' => ['36'],
			'appveyor-phpunit.xml' => ['14'],
			'Gemfile' => ['36'],
			'grunt-settings.yaml' => ['36'],
			'grunt-readme.md' => ['36'],
			'Gruntfile.js' => ['36'],
			'karma.conf.js' => ['14'],
			'phpunit.xml.dist' => ['14'],
			'scss-lint-report.xml' => ['36'],
			'sccs-lint.yml' => ['36'],
			'travisci-phpunit.xml' => ['14'],
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
