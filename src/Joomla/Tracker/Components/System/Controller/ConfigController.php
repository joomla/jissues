<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\Controller;

use Joomla\Factory;
use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Class ConfigController.
 *
 * @since  1.0
 */
class ConfigController extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'config';

	/**
	 * Execute the controller.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		parent::execute();
	}
}
