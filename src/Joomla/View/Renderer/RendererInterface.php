<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\View\Renderer;

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
	 *
	 * @since   1.0
	 */
	public function render($template = '', array $data = array());

	/**
	 * Set the template.
	 *
	 * @param   string  $name  The name of the template file.
	 *
	 * @return  RendererInterface
	 *
	 * @since   1.0
	 */
	public function setTemplate($name);

	/**
	 * Sets the paths where templates are stored.
	 *
	 * @param   string|array  $paths            A path or an array of paths where to look for templates.
	 * @param   bool          $overrideBaseDir  If true a path can be outside themes base directory.
	 *
	 * @return  RendererInterface
	 *
	 * @since   1.0
	 */
	public function setTemplatesPaths($paths, $overrideBaseDir = false);

	/**
	 * Set the templates location paths.
	 *
	 * @param   string  $path  Templates location path.
	 *
	 * @return  RendererInterface
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
	 * @return  RendererInterface
	 *
	 * @since   1.0
	 */
	public function set($key, $value);

	/**
	 * Unset a particular variable.
	 *
	 * @param   mixed  $key  The variable name
	 *
	 * @return  RendererInterface
	 *
	 * @since   1.0
	 */
	public function unsetData($key);
}
