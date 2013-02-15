<?php
/**
 * @package    Joomla.Build
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Set flag that this is a parent file.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.php';

JLoader::registerPrefix('J', __DIR__ . '/libraries');

require_once JPATH_LIBRARIES . '/cms.php';

/**
 * This script will recompile the CSS files for templates using Less to build their stylesheets.
 *
 * @package  Joomla.Build
 * @since    3.0
 */
class GenerateCss extends JApplicationCli
{
	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function doExecute()
	{
		$templates = array(
			JPATH_ADMINISTRATOR . '/templates/isis/less/template.less' => JPATH_ADMINISTRATOR . '/templates/isis/css/template.css',
			JPATH_SITE . '/templates/joomla/less/template.less' => JPATH_SITE . '/templates/joomla/css/template.css'
		);
		$less = new JLess;

		foreach ($templates as $source => $output)
		{
			try
			{
				$less->compileFile($source, $output);
			}
			catch (Exception $e)
			{
				echo $e->getMessage();
			}
		}
	}
}

JApplicationCli::getInstance('GenerateCss')->execute();
