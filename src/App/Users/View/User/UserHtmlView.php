<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\View\User;

use App\Users\Table\UsersTable;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * User view class for the Users component
 *
 * @since  1.0
 */
class UserHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Item ID
	 *
	 * @var    UsersTable
	 * @since  1.0
	 */
	private $item = null;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer
			->set('item', $this->getItem())
			->set('tz_offset', (new \DateTimeZone($this->getItem()->getParams()->get('timezone', 'UTC')))->getOffset(new \DateTime) / 3600);

		return parent::render();
	}

	/**
	 * Get an item.
	 *
	 * @return UsersTable
	 *
	 * @since   1.0
	 */
	public function getItem()
	{
		if (!$this->item)
		{
			throw new \RuntimeException('Item not set');
		}

		return $this->item;
	}

	/**
	 * Set the item.
	 *
	 * @param   UsersTable  $item  The item.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setItem(UsersTable $item)
	{
		$this->item = $item;

		return $this;
	}
}
