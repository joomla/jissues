<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\IssueCategoryMappingTable;
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
		$db     = $this->getDb();
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
	protected function getByName($name = '')
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
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$db->setQuery(
			$query->delete('#__issue_category_map')
				->where('category_id' . '=' . $id)
		)->execute();

		// Delete the category from the table
		$table = new CategoryTable($db);

		$table->delete($id);

		return $this;
	}

	/**
	 * Save the category/categories of an issues
	 *
	 * @param   array  $src  The source, should contain three parts, $src['issue_id'] is the id of the issue, $src['created_by']
	 *                       should be the create-user's id, and $src['categories'] should be an array of category id(s).
	 *
	 * @throws  \RuntimeException
	 *
	 * @return  $this This allows chaining
	 *
	 * @since 1.0
	 */
	public function saveCategory(array $src)
	{
		$filter = new InputFilter;

		$data       = array();
		$issue_id   = $filter->clean($src['issue_id'], 'int');
		$created_by = $filter->clean($src['created_by'], 'int');

		foreach ($src['categories'] as $key => $category)
		{
			$data[$key]['issue_id']    = $issue_id;
			$data[$key]['created_by']  = $created_by;
			$data[$key]['category_id'] = $filter->clean($category);
		}

		$db    = $this->getDb();

		foreach ($data as $item)
		{
			$table = new IssueCategoryMappingTable($db);
			$table->save($item);
		}

		return $this;
	}

	public function getCategories($issue_id)
	{
		$filter = new InputFilter;
		$issue_id   = $filter->clean($issue_id, 'int');

		$db = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('category_id')->from('#__issue_category_map')->where('issue_id = '. $issue_id );
		return $db->setQuery($query)->loadObjectList();
	}

	public function updateCategory(array $src)
	{
		$new_category = $src['categories'];
		$old_src = $this->getCategories($src['issue_id']);
		$old_category = array();
		foreach($old_src as $category)
		{
			$old_category[] = $category->category_id;
		}

		$delete = array_diff($old_category, $new_category);
		$insert = array_diff($new_category, $old_category);
		$db = $this->getDb();
		if($delete){
			$query = $db->getQuery(true);
			$query->delete('#__issue_category_map')->where('issue_id = '. (int) $src['issue_id'])
				->where('category_id IN ('. implode(', ', $delete) . ')');
			$db->setQuery($query)->execute();
		}
		if($insert){
			$src['categories'] = $insert;
			$this->saveCategory($src);
		}


		return $this;
	}
}
