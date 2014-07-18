<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Model;

use JTracker\Model\AbstractDoctrineListModel;

/**
 * Articles model class for the Text component.
 *
 * @since  1.0
 */
class ArticlesModel extends AbstractDoctrineListModel
{
	/**
	 * The name of the entity.
	 *
	 * @var string
	 *
	 * @since  1.0
	 */
	protected $entityName = 'Article';

	/**
	 * Get the list query.
	 *
	 * This method must be implemented in child classes.
	 *
	 * @throws \RuntimeException
	 * @return string
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		return 'SELECT a FROM App\Text\Entity\Article a';
	}
}
