<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\Mocks;

use App\Tracker\Controller\Hooks\Listeners\JoomlacmsPullsListener;

/**
 * Class JoomlaCmsPullsListenerMock
 *
 * @since  1.0
 */
class JoomlaCmsPullsListenerMock extends JoomlacmsPullsListener
{
	/**
	 * Method to expose an underlying protected method
	 *
	 * @param   array  $files  Files array
	 *
	 * @return array
	 */
	public function testCheckFilesAndAssignCategory(array $files)
	{
		return $this->checkFilesAndAssignCategory($files);
	}
}
