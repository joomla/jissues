<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssueCategoryMappingTable;
use JTracker\Model\AbstractTrackerDatabaseModel;
use Joomla\Filter\InputFilter;
use App\Tracker\Table\CategoryTable;
use Joomla\String\String;
use Joomla\Date\Date;

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

		$filter              = new InputFilter;
		$data['title']       = $filter->clean($src['title'], 'string');
		$data['alias']       = $filter->clean($src['alias'], 'cmd');
		$data['color']       = $filter->clean($src['color'], 'string');
		$data['project_id']  = $this->getProject()->project_id;

		$table = new CategoryTable($this->getDb());

		$table->save($data);

		return $this;
	}

	/**
	 * Get an item.
	 *
	 * @param   integer  $id  The id of the category
	 *
	 * @return  CategoryTable
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
		$data['id']          = $filter->clean($src['id'], 'uint');
		$data['title']       = $filter->clean($src['title'], 'string');
		$data['alias']       = $filter->clean($src['alias'], 'cmd');
		$data['color']       = $filter->clean($src['color'], 'string');
		$data['project_id']  = $this->getProject()->project_id;

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
	public function getByName($name = '')
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
	 * Get an item by alias
	 *
	 * @param   string  $alias  The alias of the category
	 *
	 * @return  object
	 *
	 * @since 1.0
	 */
	public function getByAlias($alias = '')
	{
		$db        = $this->getDb();
		$query     = $db->getQuery(true);
		$projectId = $this->getProject()->project_id;

		$query->select('*')
			->from('#__issues_categories')
			->where($db->quoteName('project_id') . '=' . $projectId);

		$filter = new InputFilter;
		$alias = $filter->clean($alias, 'cmd');

		if ($alias)
		{
			$alias = $db->quote('%' . $db->escape(String::strtolower($alias), true) . '%', false);
			$query->where($db->quoteName('alias') . ' LIKE ' . $alias);
		}

		$item = $db->setQuery($query)->loadObject();

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
	 * @param   array  $src  The source, should contain three parts, $src['issue_id'] is the id of the issue,
	 *                       and $src['categories'] should be an array of category id(s).
	 *
	 * @throws  \RuntimeException
	 *
	 * @return  $this This allows chaining
	 *
	 * @since 1.0
	 */
	public function saveCategory(array $src)
	{
		$data       = array();
		$issue_id   = (int) $src['issue_id'];

		if ($src['categories'])
		{
			foreach ($src['categories'] as $key => $category)
			{
				$data[$key]['issue_id']    = $issue_id;
				$data[$key]['category_id'] = (int) ($category);
			}

			$db = $this->getDb();

			foreach ($data as $item)
			{
				$table = new IssueCategoryMappingTable($db);
				$table->save($item);
			}
		}

		return $this;
	}

	/**
	 * Get issue's category ids by issue's id.
	 *
	 * @param   int  $issueId  The id of the issue.
	 *
	 * @since   1.0
	 *
	 * @return  array  The object list of the issues.
	 */
	public function getCategories($issueId)
	{
		$issueId = (int) $issueId;

		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('category_id')->from('#__issue_category_map')->where('issue_id = ' . $issueId);

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Update the issue - category mapping with given source, method allows chaining.
	 *
	 * @param   array  $src  The source of the category, should include: $src['issue_id'], the issue's id; $src['categories'],
	 *                       the category ids' array.
	 *
	 * @since   1.0
	 *
	 * @return  $this
	 */
	public function updateCategory(array $src)
	{
		$newCategories    = ($src['categories']) ? $src['categories'] : array();
		$oldSrc           = $this->getCategories($src['issue_id']);
		$oldCategories    = array();
		$data             = array();
		$data['issue_id'] = (int) $src['issue_id'];

		foreach ($oldSrc as $category)
		{
			$oldCategories[] = $category->category_id;
		}

		$delete = array_diff($oldCategories, $newCategories);
		$insert = array_diff($newCategories, $oldCategories);
		$db     = $this->getDb();

		if ($delete)
		{
			$query = $db->getQuery(true);
			$query->delete('#__issue_category_map')->where('issue_id = ' . $data['issue_id'])
				->where('category_id IN (' . implode(', ', $delete) . ')');
			$db->setQuery($query)->execute();
		}

		if ($insert)
		{
			$data['categories'] = $insert;
			$this->saveCategory($data);
		}

		if ($insert || $delete)
		{
			$changes                 = array();
			$changes['modified_by']  = $src['modified_by'];
			$changes['issue_number'] = $src['issue_number'];
			$changes['project_id']   = $src['project_id'];
			$changes['old']          = $oldCategories;
			$changes['new']          = $newCategories;

			$this->processChanges($changes);
		}

		return $this;
	}

	/**
	 * Get the Issue ids by category ID, returning the object list.
	 *
	 * @param   int  $categoryId  The id of the category.
	 *
	 * @since   1.0
	 *
	 * @return  array  The object array of the issue ids.
	 */
	public function getIssueIdsByCategory($categoryId)
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('issue_id')->from('#__issue_category_map')->where('category_id = ' . (int) $categoryId);

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Process the change in category for issues.
	 *
	 * @param   array  $src  The source, should include: $src['issue_number'], the issue's number; $src['project_id'],
	 *                       the issue's project id; $src['old'] and $src['new'] for old and new categories; $src['modified_by'],
	 *                       modified username.
	 *
	 * @since   1.0
	 *
	 * @return  $this
	 */
	private function processChanges(array $src)
	{
		$date = new Date;
		$date = $date->format($this->getDb()->getDateFormat());

		$change       = new \stdClass;
		$change->name = 'category';
		$change->old  = array();
		$change->new  = array();

		foreach ($src['old'] as $key => $old)
		{
			$oldCategory                = $this->getItem($old);
			$change->old[$key]['title'] = $oldCategory->title;
			$change->old[$key]['color'] = $oldCategory->color;
		}

		foreach ($src['new'] as $key => $new)
		{
			$newCategory                = $this->getItem($new);
			$change->new[$key]['title'] = $newCategory->title;
			$change->new[$key]['color'] = $newCategory->color;
		}

		$data                 = array();
		$data['event']        = 'change';
		$data['created_date'] = $date;
		$data['user']         = $src['modified_by'];
		$data['issue_number'] = (int) $src['issue_number'];
		$data['project_id']   = (int) $src['project_id'];
		$data['text']         = json_encode(array($change));

		$table = new ActivitiesTable($this->getDb());
		$table->save($data);

		return $this;
	}
}
