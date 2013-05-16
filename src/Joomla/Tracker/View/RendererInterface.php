<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View;

/**
 * Class RendererInterface.
 *
 * @since  1.0
 */
interface RendererInterface
{
	/**
	 * Render and return compiled HTML.
	 *
	 * @param   string  $template  The template file name
	 * @param   mixed   $data      The data to pass to the template
	 *
	 * @return  string  compiled HTML
	 */
	public function render($template = '', array $data = array());

	/**
	 * Set the template.
	 *
	 * @param   string  $name  The name of the template file.
	 *
	 * @return  $this
	 *
	 * @since  1.0
	 */
	public function setTemplate($name);

	/**
	 * Sets the paths where templates are stored.
	 *
	 * @param   string|array  $paths            A path or an array of paths where to look for templates.
	 * @param   bool          $overrideBaseDir  If true a path can be outside themes base directory.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setTemplatesPaths($paths, $overrideBaseDir = false);

	/**
	 * Set the templates location paths.
	 *
	 * @param   string  $path  Templates location path.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function addPath($path);

	/**
	 * Set the data.
	 *
	 * @param   mixed  $key    The variable name or an array of variable names with values.
	 * @param   mixed  $value  The value.
	 *
	 * @return  $this
	 */
	public function set($key, $value);

	/**
	 * Unset a particular variable.
	 *
	 * @param   mixed  $key  The variable name
	 *
	 * @return  $this
	 */
	public function unsetData($key);

	/**
	 * Add a filter.
	 *
	 * @param   string  $name    The filter name.
	 * @param   object  $filter  The filter.
	 *
	 * @return mixed
	 */
	public function addFilter($name, $filter = null);

	public function addFunction($function);
}
