<?php
/**
 * Part of the Joomla Framework GitHub Package
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\DataType;

/**
 * Class GitHub DataType Commit Status.
 *
 * @since  1.0
 */
class Commit
{
	public $sha = '';

	public $author_name = '';

	public $author_date = '';

	public $committer_name = '';

	public $committer_date = '';

	public $message = '';
}
