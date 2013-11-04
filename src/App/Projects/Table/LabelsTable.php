<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Table;

use Joomla\DI\Container;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__tracker_projects table
 *
 * @property   integer  $label_id    PK
 * @property   integer  $project_id  Project ID
 * @property   string   $name        Label name
 * @property   string   $color       Label color
 *
 * @since  1.0
 */
class LabelsTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		parent::__construct($container, '#__tracker_labels', 'label_id');
	}
}
