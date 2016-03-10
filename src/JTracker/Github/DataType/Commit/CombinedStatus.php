<?php
/**
 * Part of the Joomla Framework GitHub Package
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\DataType\Commit;

/**
 * Class GitHub DataType Commit CombinedStatus.
 *
 * @since  1.0
 */
class CombinedStatus
{
	/**
	 * @var string
	 */
	public $state = '';

	/**
	 * @var Status[]
	 */
	public $statuses = [];
}
