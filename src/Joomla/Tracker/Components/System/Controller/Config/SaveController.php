<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\Controller\Config;

use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Class SaveController.
 *
 * @since  1.0
 */
class SaveController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @since   1.0
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		$config = $this->getApplication()->input->get('config', array(), 'array');

		if (!$config)
		{
			throw new \UnexpectedValueException('No config to save...');
		}

		echo '<h1>SaveConfig</h1>';
		echo '<h2>Please Copy&amp;Paste + Save =;)</h2>@todo saveMe...';

		echo '<pre style="background-color: #ffc; color: darkred; padding: 1em; border: 3px solid lime;">';

		echo json_encode($config, JSON_PRETTY_PRINT);

		// @todo write a small JSON prettyPrint function for PHP < 5.4

		echo '</pre>';
	}
}
