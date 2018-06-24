<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

use JTracker\Pagination\TrackerPagination;

/**
 * Abstract model to get data for a list view
 *
 * @since  1.0
 */
abstract class AbstractTrackerListModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Internal memory based cache array of data.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $cache = [];

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
	protected $query;

	/**
	 * Input object
	 *
	 * @var    Input
	 * @since  1.0
	 */
	protected $input;

	/**
	 * Pagination object
	 *
	 * @var    TrackerPagination
	 * @since  1.0
	 */
	protected $pagination;

	/**
	 * Instantiate the model.
	 *
	 * @param   DatabaseDriver  $database  The database driver.
	 * @param   Input           $input     The input object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database, Input $input)
	{
		parent::__construct($database);

		$this->input = $input;

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
	 * Method to get an array of data items for ajax requests
	 *
	 * @return mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getAjaxItems()
	{
		$store = $this->getStoreID();

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$query = $this->_getAjaxListQuery();

		$items = $this->_getList($query, $this->getStart(), $this->state->get('list.limit'));

		// Add the items to the internal cache.
		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	abstract protected function getListQuery();

	/**
	 * Set the pagination object.
	 *
	 * @param   TrackerPagination  $pagination  The pagination object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setPagination(TrackerPagination $pagination)
	{
		$this->pagination = $pagination;
	}

	/**
	 * Method to get the pagination object for the data set.
	 *
	 * @return  TrackerPagination  The pagination object for the data set.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
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

		if (is_null($this->pagination))
		{
			throw new \UnexpectedValueException('Pagination not set');
		}

		// Setup the values to paginate over.
		$limit = (int) $this->state->get('list.limit') - (int) $this->state->get('list.links');

		$this->pagination->setValues($this->getTotal(), $this->getStart(), $limit);

		// Add the object to the internal cache.
		$this->cache[$store] = $this->pagination;

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

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	/**
	 * Load the model state.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function loadState()
	{
		// Check whether the state has already been loaded
		if (!($this->state instanceof Registry))
		{
			$this->state = new Registry;
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
			$query->clear('select')->clear('order')->select('COUNT(*)');
			$this->db->setQuery($query);

			return (int) $this->db->loadResult();
		}
		else
		{
			/*
			 * Performance of this query is very bad as it forces database engine to go
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
