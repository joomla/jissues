<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Model;

use App\Text\Table\ArticlesTable;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Page model class.
 *
 * @since  1.0
 */
class PageModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   string  $alias  The item alias.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function getItem($alias)
	{
		$table = new ArticlesTable($this->db);

		return $table->load(array('alias' => $alias));
	}
}
