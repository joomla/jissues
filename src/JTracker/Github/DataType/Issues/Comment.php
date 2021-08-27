<?php
/**
 * Part of the Joomla Framework GitHub Package
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\DataType\Issues;

/**
 * Class GitHub DataType Commit Status.
 *
 * @since  1.0
 */
class Comment
{
	public $id = 0;

	public $url = '';

	public $html_url = '';

	public $body = '';

	/**
	 * @var null @todo User object class
	 */
	public $user;

	public $created_at = '';

	public $updated_at = '';
}
