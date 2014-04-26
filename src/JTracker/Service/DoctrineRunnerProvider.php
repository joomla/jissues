<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Symfony\Component\Console\Application as SymphonyApplication;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * DoctrineRunner service provider
 *
 * @since  1.0
 */
class DoctrineRunnerProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->set('Symfony\\Component\\Console\\Application\\SymphonyApplication',
			function () use ($container)
			{
				/* @type \Doctrine\ORM\EntityManager $entityManager */
				$entityManager = $container->get('EntityManager');

				$doctrineRunner = new SymphonyApplication;

				$doctrineRunner->setCatchExceptions(true);
				$doctrineRunner->setAutoExit(false);

				$doctrineRunner->setHelperSet(
					new HelperSet(
						[
							'db' => new ConnectionHelper($entityManager->getConnection()),
							'em' => new EntityManagerHelper($entityManager)
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
						new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand,
						new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand,
						new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand,
						*/
						new \Doctrine\ORM\Tools\Console\Command\InfoCommand
					]
				);

				return $doctrineRunner;
			}, true, true
		);

		// Alias the object
		$container->alias('DoctrineRunner', 'Symfony\\Component\\Console\\Application\\SymphonyApplication');
	}
}
