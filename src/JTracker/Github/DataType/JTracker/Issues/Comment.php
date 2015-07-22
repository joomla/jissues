<?php
/**
 * Part of the Joomla Framework GitHub Package
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\DataType\JTracker\Issues;

/**
 * Class GitHub DataType Commit Status.
 *
 * @since  1.0
 */
class Comment extends \JTracker\Github\DataType\Issues\Comment
{
	public $comment_id = 0;

	public $text = '';

	public $text_raw = '';

	/**
	 * @var string  username
	 */
	public $opened_by = '';

	public $activities_id = 0;
}
