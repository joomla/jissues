<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Version Field class for the Tracker application.
 * Supports an HTML select list of versions
 *
 * @package     BabDev.Tracker
 * @subpackage  Form
 * @since       1.0
 */
class JFormFieldVersion extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $type = 'Version';

	/**
	 * Method to get the field options for version
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.0
	 */
	protected function getOptions()
	{
		return JHtml::_('version.options');
	}
}
