<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\View\User;

use Joomla\Factory;
use Joomla\View\AbstractHtmlView;

/**
 * Default view class for the tracker component
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class UserHtmlView extends AbstractHtmlView
{
	protected $item;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		$itemId = Factory::$application->input->getUint('id');

		if (!$itemId)
		{
			$user = Factory::$application->getUser();

			if (!$user->id)
			{
				throw new \RuntimeException('You are not logged in');
			}

			$itemId = $user->id;
		}

		$this->item = $this->model->getItem($itemId);

		return parent::render();
	}
}
