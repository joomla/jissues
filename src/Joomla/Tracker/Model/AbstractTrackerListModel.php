<?php
/**
 * @package     JTracker\Model
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Model;

use Joomla\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;

/**
 * Abstract model to get data for a list view
 *
 * @package     JTracker
 * @subpackage  Model
 * @since       1.0
 */
abstract class AbstractTrackerListModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Internal memory based cache array of data.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $cache = array();

	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = null;

	/**
	 * An internal cache for the last query used.
	 *
	 * @var    DatabaseQuery
	 * @since  1.0
	 */
	protected $query = array();

	/**
	 * Instantiate the model.
	 *
	 * @since  1.0
	 */
	public function __construct(DatabaseDriver $database = null)
	{
		parent::__construct($database);

		// Set the context if not already done
		if (is_null($this->context))
		{
			$this->context = strtolower($this->option . '.' . $this->getName());
		}

		// Populate the state
		$this->loadState();
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the query for the list
		$query = $this->_getListQuery();

		$items = $this->_getList($query, $this->getStart(), $this->state->get('list.limit'));

		// Add the items to the internal cache.
		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery   A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	abstract protected function getListQuery();

	/**
	 * Method to get a JPagination object for the data set.
	 *
	 * @return  JPagination  A JPagination object for the data set.
	 *
	 * @since   1.0
	 */
	public function getPagination()
	{
		// Get a storage key.
		$store = $this->getStoreId('getPagination');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Create the pagination object.
		$limit = (int) $this->state->get('list.limit') - (int) $this->state->get('list.links');
		$page  = new JPagination($this->getTotal(), $this->getStart(), $limit);

		// Add the object to the internal cache.
		$this->cache[$store] = $page;

		return $this->cache[$store];
	}

	/**
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   1.0
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getstart');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = (int) $this->state->get('list.start');
		$limit = (int) $this->state->get('list.limit');
		$total = $this->getTotal();
		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $start;

		return $this->cache[$store];
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.0
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->state->get('list.start');
		$id .= ':' . $this->state->get('list.limit');
		$id .= ':' . $this->state->get('list.ordering');
		$id .= ':' . $this->state->get('list.direction');

		return md5($this->context . ':' . $id);
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.0
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total.
		$query = $this->_getListQuery();

		$total = (int) $this->_getListCount($query);
		/*
		try
		{
			$total = (int) $this->_getListCount($query);
		}
		catch (\RuntimeException $e)
		{
			Factory::$application->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
		*/

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	/**
	 * Load the model state.
	 *
	 * @return  Registry  The state object.
	 *
	 * @since   1.0
	 */
	protected function loadState()
	{
		// Check whether the state has already been loaded
		if (!($this->state instanceof Registry))
		{
			$this->state = parent::loadState();
		}

		// If the context is set, assume that stateful lists are used.
		if ($this->context)
		{
			$app = Factory::$application;

			$value = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('system.list_limit', 20), 'uint');
			$limit = $value;
			$this->state->set('list.limit', $limit);

			$value = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0);
			$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
			$this->state->set('list.start', $limitstart);
		}
		else
		{
			$this->state->set('list.start', 0);
			$this->state->set('list.limit', 0);
		}
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string   $query       The query.
	 * @param   integer  $limitStart  Offset.
	 * @param   integer  $limit       The number of records.
	 *
	 * @return  array  An array of results.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitStart = 0, $limit = 0)
	{
		$this->db->setQuery($query, $limitStart, $limit);
		$result = $this->db->loadObjectList();

		return $result;
	}

	/**
	 * Returns a record count for the query
	 *
	 * @param   string  $query  The query.
	 *
	 * @return  integer  Number of rows for query
	 *
	 * @since   1.0
	 */
	protected function _getListCount($query)
	{
		if ($query instanceof DatabaseQuery)
		{
			// Create COUNT(*) query to allow database engine to optimize the query.
			$query = clone $query;
			$query->clear('select')->clear('order')->clear('join')->select('COUNT(*)');
			$this->db->setQuery($query);

			return (int) $this->db->loadResult();
		}
		else
		{
			/* Performance of this query is very bad as it forces database engine to go
			 * through all items in the database. If you don't use JDatabaseQuery object,
			 * you should override this function in your model.
			 */
			$this->db->setQuery($query);
			$this->db->execute();

			return $this->db->getNumRows();
		}
	}

	/**
	 * Method to cache the last query constructed.
	 *
	 * This method ensures that the query is constructed only once for a given state of the model.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object
	 *
	 * @since   1.0
	 */
	protected function _getListQuery()
	{
		// Capture the last store id used.
		static $lastStoreId;

		// Compute the current store id.
		$currentStoreId = $this->getStoreId();

		// If the last store id is different from the current, refresh the query.
		if ($lastStoreId != $currentStoreId || empty($this->query))
		{
			$lastStoreId = $currentStoreId;
			$this->query = $this->getListQuery();
		}

		return $this->query;
	}
}
