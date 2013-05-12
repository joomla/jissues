<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\View\Config;

use Joomla\View\AbstractHtmlView;

/**
 * Config view.
 *
 * @since  1.0
 */
class ConfigHtmlView extends AbstractHtmlView
{
	/**
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $config;

	/**
	 * Method to render the view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 *
	 * @return  string  The rendered view.
	 */
	public function render()
	{
		$this->config = json_decode(file_get_contents(JPATH_CONFIGURATION . '/config.json'));

		return parent::render();
	}
}
