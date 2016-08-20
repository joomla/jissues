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
 * Article model class.
 *
 * @since  1.0
 */
class ArticleModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   integer  $id  The item id.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		return (new ArticlesTable($this->db))->load($id)->getIterator();
	}
}
