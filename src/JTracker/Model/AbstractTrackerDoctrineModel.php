<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

use App\Debug\Database\SQLLogger;
use App\Debug\Database\SQLLoggerAwareInterface;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Setup;

use Joomla\Filter\InputFilter;
use Joomla\Model\AbstractModel;

use JTracker\ORM\Custom\TablePrefix;
use JTracker\ORM\Mapping\Filter;

/**
 * Abstract base model for the tracker application.
 *
 * NOTE: This class extends the AbstractModel class to be "compatible" with JView - 2BDeprecated...
 *
 * @since  1.0
 */
abstract class AbstractTrackerDoctrineModel extends AbstractModel implements SQLLoggerAwareInterface
{
	/**
	 * The model (base) name
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $name = null;

	/**
	 * The URL option for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $app = null;

	/**
	 * The Entity name.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $entityName;

	/**
	 * The database connection configuration.
	 *
	 * @var \Joomla\Registry\Registry
	 * @since  1.0
	 */
	private $connectionConfig;

	/**
	 * Array of paths containing entities.
	 *
	 * @var array
	 * @since  1.0
	 */
	private $entityPaths;

	/**
	 * Debug flag.
	 *
	 * @var boolean
	 * @since  1.0
	 */
	private $debug;

	/**
	 * A SQL query logger object.
	 *
	 * @var    SQLLogger
	 * @since  1.0
	 */
	private $logger;

	/**
	 * Instantiate the model.
	 *
	 * @param   \Joomla\Registry\Registry  $connectionConfig  The database connection configuration.
	 * @param   array                      $entityPaths       Array of paths containing entities.
	 * @param   boolean                    $debug             Debug flag.
	 *
	 * @since    1.0
	 */
	public function __construct($connectionConfig, array $entityPaths, $debug = false)
	{
		$this->connectionConfig = $connectionConfig;
		$this->entityPaths = $entityPaths;
		$this->debug = $debug;

		// Guess the option from the class name (Option)Model(View).
		if (empty($this->app))
		{
			// Get the fully qualified class name for the current object
			$fqcn = (get_class($this));

			// Strip the base component namespace off
			$className = str_replace('App\\', '', $fqcn);

			// Explode the remaining name into an array
			$classArray = explode('\\', $className);

			// Set the component as the first object in this array
			$this->app = $classArray[0];
		}

		// Set the view name
		if (empty($this->name))
		{
			$this->getName();
		}
	}

	/**
	 * Get the Entity Manager object.
	 *
	 * @return EntityManager
	 *
	 * @throws \Doctrine\ORM\ORMException
	 *
	 * @since    1.0
	 */
	protected function getEntityManager()
	{
		static $entityManager;

		if (!$entityManager)
		{
			$config = Setup::createAnnotationMetadataConfiguration($this->entityPaths, $this->debug);

			if ($this->debug)
			{
				// Set up logging
				$config->setSQLLogger($this->logger);
			}

			// Database configuration parameters
			$connectionParams = [
				'driver'   => $this->connectionConfig->get('driver'),
				'host'     => $this->connectionConfig->get('host'),
				'user'     => $this->connectionConfig->get('user'),
				'password' => $this->connectionConfig->get('password'),
				'dbname'   => $this->connectionConfig->get('name'),
			];

			// Obtaining the event manager
			$eventManager = new EventManager;

			// Table Prefix
			$eventManager->addEventListener(
				Events::loadClassMetadata,
				new TablePrefix($this->connectionConfig->get('prefix'))
			);

			// Obtaining the entity manager
			$entityManager = EntityManager::create($connectionParams, $config, $eventManager);
		}

		return $entityManager;
	}

	/**
	 * Method to get the model name
	 *
	 * The model name. By default parsed using the class name or it can be set
	 * by passing a $config['name'] in the class constructor
	 *
	 * @return  string  The name of the model
	 *
	 * @since   1.0
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			// Get the fully qualified class name for the current object
			$fqcn = (get_class($this));

			// Explode the name into an array
			$classArray = explode('\\', $fqcn);

			// Get the last element from the array
			$class = array_pop($classArray);

			// Remove Model from the name and store it
			$this->name = str_replace('Model', '', $class);
		}

		return $this->name;
	}

	/**
	 * Get the entity class for the current model.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	public function getEntityClass()
	{
		return 'App\\' . $this->app . '\Entity\\' . $this->entityName;
	}

	/**
	 * Get a list of items.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		return $this->getEntityManager()
			->getRepository($this->getEntityClass())
			->findAll();
	}

	/**
	 * Get a single item.
	 *
	 * @param   integer  $id  The ID.
	 *
	 * @return object
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		return $this->getEntityManager()
			->find($this->getEntityClass(), $id);
	}

	/**
	 * Find a single item by condition(s).
	 *
	 * @param   array  $conditions  Indexed array with conditions.
	 *
	 * @return null|object
	 *
	 * @since   1.0
	 */
	public function findOneBy(array $conditions)
	{
		return $this->getEntityManager()
			->getRepository($this->getEntityClass())
			->findOneBy($conditions);
	}

	/**
	 * Delete an item.
	 *
	 * @param   integer  $id  The ID.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function delete($id)
	{
		$item = $this->getEntityManager()->find($this->getEntityClass(), (int) $id);

		if (!$item)
		{
			throw new \UnexpectedValueException('Invalid item');
		}

		$this->getEntityManager()->remove($item);
		$this->getEntityManager()->flush();

		return $this;
	}

	/**
	 * Save the entity with request data.
	 *
	 * @param   array  $data  The request data.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function save(array $data)
	{
		$filter = new InputFilter;

		$id = isset($data['id']) ? $filter->clean($data['id'], 'uint') : 0;

		$entityClass = $this->getEntityClass();

		$entity = ($id)
			? $this->getEntityManager()->find($entityClass, $id)
			: new $entityClass;

		$this->bindAndValidate($entity, $data);

		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();

		return $this;
	}

	/**
	 * Bind request data to an Entity.
	 *
	 * @param   object  $entity  The entity.
	 * @param   array   $data    The request data.
	 *
	 * @return object
	 *
	 * @since   1.0
	 */
	protected function bindAndValidate($entity, array $data)
	{
		$reader = new SimpleAnnotationReader;

		$inputFilter = new InputFilter;

		$reflectionClass = new \ReflectionClass(get_class($entity));

		$reader->addNamespace('Doctrine\ORM\Mapping');

		// @todo this does NOT WORK :( ...
		$reader->addNamespace('JTracker\ORM\Mapping');

		// @todo ... so we instantiate a DUMMY :|
		new Filter;

		foreach ($data as $k => $v)
		{
			$setMethod = 'set' . $k;

			if (false == $reflectionClass->hasMethod($setMethod) || false == $reflectionClass->hasProperty($k))
			{
				continue;
			}

			// $test = $reader->getPropertyAnnotations($reflectionClass->getProperty($k));

			/* @type \JTracker\ORM\Mapping\Filter $propFilter */
			$propFilter = $reader->getPropertyAnnotation($reflectionClass->getProperty($k), 'JTracker\ORM\Mapping\Filter');

			$filter = 'cmd';

			if ($propFilter)
			{
				$filter = $propFilter->type;
			}

			$value = $inputFilter->clean($v, $filter);

			$entity->$setMethod($value);
		}

		return $this;
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param   SQLLogger  $logger  Interface for SQL loggers.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setSQLLogger(SQLLogger $logger)
	{
		$this->logger = $logger;

		return $this;
	}
}
