<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\ORM\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Filter annotation class.
 *
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 *
 * @since  1.0
 */
final class Filter implements Annotation
{
	/**
	 * Validation type.
	 *
	 * @see \Joomla\Filter\InputFilter::clean()
	 *
	 * @var string
	 */
	public $type = 'string';

	/**
	 * @var integer
	 */
	public $length = 0;

	/**
	 * @var boolean
	 */
	public $unique = false;
}
