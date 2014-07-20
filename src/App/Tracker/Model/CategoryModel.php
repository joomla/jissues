<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use JTracker\Model\AbstractTrackerDatabaseModel;
use Joomla\Filter\InputFilter;
use App\Tracker\Table\CategoryTable;

/**
 * Model of categories
 * Class CategoryModel
 *
 * @package  App\Tracker\Model
 *
 * @since    1.0
 */
class CategoryModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Add an item
	 *
	 * @param   array  $src  The source
	 *
	 * @return  $this  This allows chaining
	 *
	 * @since  1.0
	 */
	public function add(array $src)
	{
		$data = array();

		$filter             = new InputFilter;
		$data['name']       = $filter->clean($src['name'], 'string');
		$data['project_id'] = $this->getProject()->project_id;

		$table = new CategoryTable($this->getDb());

		$table->check()->save($data);

		return $this;
	}

	/**
	 * Get an item.
	 *
	 * @param   integer  $id  The id of the category
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		if ($id == null)
		{
			throw new \RuntimeException(g11n3t('Missing ID'));
		}

		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$table = new CategoryTable($db);
		$item  = $db->setQuery(
			$query->select('*')
				->from($db->quoteName($table->getTableName()))
				->where($db->quoteName('id') . '=' . (int) $id)
		)->loadObject();

		if (!$item)
		{
			throw new \RuntimeException(g11n3t('Invalid Category'));
		}

		return $item;
	}

	/**
	 * Save an item
	 *
	 * @param   array  $src  The source
	 *
	 * @throws  \RuntimeException
	 *
	 * @return  $this This allows chaining
	 *
	 * @since 1.0
	 */
	public function save(array $src)
	{
		$filter = new InputFilter;

		$data = array();

		$data['id']          = $filter->clean($src['id'], 'int');
		$data['projects_id'] = $filter->clean($src['project_id'], 'int');
		$data['name']        = $filter->clean($src['name'], 'string');

		if ($data['id'] == null)
		{
			throw new \RuntimeException('Missing ID');
		}

		$table = new CategoryTable($db);

		$table->load($data['id'])
			->save($data);

		return $this;
	}

	/**
	 * Get an item by name
	 *
	 * @param   string  $name  The name of the category
	 *
	 * @return  object
	 *
	 * @since 1.0
	 */
	protected function getByName($name= '')
	{
		$db        = $this->getDb();
		$query     = $db->getQuery(true);
		$projectId = $this->getProject()->project_id;

		$item = $db->setQuery(
			$query->select('*')
				->from('#__issues_categories')
				->where($db->quoteName('name') . '=' . $name)
				->where($db->quoteName('project_id') . '=' . $projectId)
		)->loadObject();

		return $item;
	}

	/**
	 * Delete a category.
	 *
	 * @param   integer  $id  The id of the category
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function delete($id)
	{
		// Remove the category-issue mapping
		$db        = $this->getDb();
		$query     = $db->getQuery(true);

		$db->setQuery(
			$query->delete('#__issue_category_map')
				->where('category_id' . '=' . $id)
		)->execute();

		// Delete the category from the table
		$table = new CategoryTable($db);

		$table->delete($id);

		return $this;
	}
}