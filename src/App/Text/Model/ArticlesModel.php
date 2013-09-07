<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Model;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Articles model class.
 *
 * @since  1.0
 */
class ArticlesModel extends AbstractTrackerListModel
{
	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		return $this->db->getQuery(true)
			->select($this->db->quoteName(array('article_id', 'title', 'alias', 'text')))
			->from($this->db->quoteName('#__articles'));
	}
}
