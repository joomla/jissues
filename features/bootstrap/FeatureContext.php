<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

use Behat\MinkExtension\Context\MinkContext;

/**
 * Behat context class.
 *
 * @since  1.0
 */
class FeatureContext extends MinkContext
{
	/**
	 * Dummy login class.
	 *
	 * @When /^I dummy-login as "([^"]*)"$/
	 *
	 * @todo code style conflicting with PHPStorm ... reorder @param
	 *
	 * @param   string  $loginType  The login type.
	 *
	 * @return void
	 */
	public function iDummyLoginAs($loginType)
	{
		$this->visit('/local-login/' . $loginType);
	}

	/**
	 * Dump the contents for debugging purpose.
	 *
	 * @Then /^I dump the contents$/
	 *
	 * @return void
	 */
	public function iDumpTheContents()
	{
		echo 'DUMP START' . PHP_EOL;

		print_r($this->getSession()->getPage()->getContent());

		echo  PHP_EOL . 'DUMP END';
	}
}
