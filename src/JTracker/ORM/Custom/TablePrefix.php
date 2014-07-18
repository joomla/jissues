<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\ORM\Custom;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Table prefix service provider
 *
 * @since  1.0
 */
class TablePrefix
{
	/**
	 * The prefix.
	 *
	 * @var string
	 *
	 * @since   1.0
	 */
	protected $prefix = '';

	/**
	 * Constructor.
	 *
	 * @param   string  $prefix  The prefix.
	 *
	 * @since   1.0
	 */
	public function __construct($prefix)
	{
		$this->prefix = (string) $prefix;
	}

	/**
	 * Load the class meta data.
	 *
	 * @param   LoadClassMetadataEventArgs  $eventArgs  Event arguments.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		/* @type ClassMetadata $classMetadata */
		$classMetadata = $eventArgs->getClassMetadata();

		$classMetadata->setPrimaryTable(
			[
				'name' => str_replace(
					'#__', $this->prefix, $classMetadata->getTableName()
				)
			]
		);

		foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping)
		{
			if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY)
			{
				$mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];

				$classMetadata->associationMappings[$fieldName]['joinTable']['name'] = str_replace(
					'#__', $this->prefix, $mappedTableName
				);
			}
		}
	}
}
