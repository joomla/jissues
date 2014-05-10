<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Model;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use JTracker\Model\AbstractTrackerDoctrineModel;

use Symfony\Component\Console\Application as SymphonyApplication;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Default model class for the Users component.
 *
 * This model is used to instantiate a CLI interface for Doctrine.
 *
 * @since  1.0
 */
class DoctrineRunnerModel extends AbstractTrackerDoctrineModel
{
	/**
	 * Get an CLI runner object - a Symphony Application.
	 *
	 * @return SymphonyApplication
	 *
	 * @since  1.0
	 */
	public function getRunner()
	{
		$doctrineRunner = new SymphonyApplication;

		$doctrineRunner->setCatchExceptions(true);
		$doctrineRunner->setAutoExit(false);

		$doctrineRunner->setHelperSet(
			new HelperSet(
				[
					'db' => new ConnectionHelper($this->getEntityManager()->getConnection()),
					'em' => new EntityManagerHelper($this->getEntityManager())
				]
			)
		);

		$doctrineRunner->addCommands(
			[
				/* DBAL Commands
				new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand,
				new \Doctrine\DBAL\Tools\Console\Command\ImportCommand,
				*/

				/* ORM Commands
				new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand,
				new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand,
				new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand,
				*/
				new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand,
				new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand,
				new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand,
				/*
				new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand,
				new \Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand,
				new \Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand,
				new \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand,
				new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand,
				*/
				new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand,
				/*
				new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand,
				*/
				new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand,
				new \Doctrine\ORM\Tools\Console\Command\InfoCommand
			]
		);

		return $doctrineRunner;
	}
}
