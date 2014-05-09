<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\EntityManager;

use Joomla\Filter\InputFilter;
use Joomla\Model\AbstractModel;
use JTracker\ORM\Mapping\Filter;

/**
 * Abstract base model for the tracker application.
 *
 * NOTE: This class extends the AbstractModel class to be "compatible" with JView - 2BDeprecated...
 *
 * @since  1.0
 */
abstract class AbstractTrackerDoctrineModel extends AbstractModel
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
	 * The EntityManager.
	 *
	 * @var    EntityManager
	 * @since  1.0
	 */
	protected $entityManager;

	/**
	 * The Entity name.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $entityName;

	/**
	 * Instantiate the model.
	 *
	 * @param   EntityManager  $entityManager  The EntityManager
	 *
	 * @since   1.0
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;

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
		return $this->entityManager
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
		return $this->entityManager
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
		return $this->entityManager
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
		$item = $this->entityManager->find($this->getEntityClass(), (int) $id);

		if (!$item)
		{
			throw new \UnexpectedValueException('Invalid item');
		}

		$this->entityManager->remove($item);
		$this->entityManager->flush();

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
			? $this->entityManager->find($entityClass, $id)
			: new $entityClass;

		$this->bindAndValidate($entity, $data);

		$this->entityManager->persist($entity);
		$this->entityManager->flush();

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
}
