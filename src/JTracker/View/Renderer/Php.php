<?php
/**
 * @copyright  Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\View\Renderer;

use Joomla\Factory;
use Joomla\Registry\Registry;
use Joomla\View\Renderer\RendererInterface;

/**
 * Class Php.
 *
 * @since  1.0
 */
class Php implements RendererInterface
{
	/**
	 * @var Registry
	 */
	protected $globals;

	protected $templatePaths = array();

	protected $debug = false;

	/**
	 * Instantiate the renderer.
	 *
	 * @param   array  $config  The array of configuration parameters.
	 *
	 * @since  1.0
	 */
	public function __construct(array $config = array())
	{
		$this->addPath((isset($config['templates_base_dir']) ? $config['templates_base_dir'] : JPATH_TEMPLATES));

		$this->debug   = JDEBUG;
		$this->globals = new Registry;
		$app = Factory::$application;

		$this->set('uri', $app->get('uri'));
	}

	/**
	 * Get a global template var.
	 *
	 * @param   string  $key  The template var key.
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		if ($this->globals->exists($key))
		{
			return $this->globals->get($key);
		}

		if ($this->debug)
		{
			trigger_error('No template var: ' . $key);
		}

		return '';
	}

	/**
	 * Render and return compiled HTML.
	 *
	 * @param   string  $template  The template file name
	 * @param   mixed   $data      The data to pass to the template
	 *
	 * @return  string  compiled HTML
	 */
	public function render($template = '', array $data = array())
	{
		$defaultPath = $this->fetchLayoutPath('default');

		$templatePath = $this->fetchLayoutPath($template);

		ob_start();

		include $defaultPath;

		$bufferDefault = ob_get_clean();

		ob_start();

		include $templatePath;

		$bufferTemplate = ob_get_clean();

		$contents = $bufferDefault;

		$contents = str_replace('[[component]]', $bufferTemplate, $contents);

		return $contents;
	}

	/**
	 * Fetch a layout file.
	 *
	 * @param   string  $template  The layout file name
	 *
	 * @throws \RuntimeException
	 *
	 * @return string The valid file path
	 */
	protected function fetchLayoutPath($template)
	{
		$fileName = $template . '.php';

		foreach ($this->templatePaths as $base)
		{
			$path = realpath($base . '/' . $fileName);

			if ($path)
			{
				return $path;
			}
		}

		$msg = '';

		$msg .= 'Template file not found: ' . $fileName;

		if (JDEBUG)
		{
			$msg .= '<br />Registered paths:<br />' . implode('<br />', $this->templatePaths);
		}

		throw new \RuntimeException($msg);
	}

	/**
	 * Set the template.
	 *
	 * @param   string  $name  The name of the template file.
	 *
	 * @return  object  Instance of this class.
	 *
	 * @since  1.0
	 */
	public function setTemplate($name)
	{
		echo __METHOD__ . print_r($name, 1);

		// TODO: Implement setTemplate() method.
		return $this;
	}

	/**
	 * Set the data.
	 *
	 * @param   mixed  $key    The variable name or an array of variable names with values.
	 * @param   mixed  $value  The value.
	 *
	 * @return  object   Instance of this class
	 */
	public function set($key, $value)
	{
		$this->globals->set($key, $value);

		return $this;
	}

	/**
	 * Unset a particular variable.
	 *
	 * @param   mixed  $key  The variable name
	 *
	 * @return  object  Instance of this class
	 */
	public function unsetData($key)
	{
		// TODO: Implement unsetData() method.
		echo __METHOD__ . print_r($key, 1);

		return $this;
	}

	/**
	 * Set the templates location paths.
	 *
	 * @param   string  $path  Templates location path.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function addPath($path)
	{
		$this->templatePaths[] = $path;

		return $this;
	}

	/**
	 * Test.
	 *
	 * @param   string  $name    Test.
	 * @param   object  $filter  Test.
	 *
	 * @return $this
	 */
	public function addFilter($name, $filter = null)
	{
		// TODO: Implement addFilter() method.
		// @echo __METHOD__ . print_r($name, 1);

		return $this;
	}

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
	public function setTemplatesPaths($paths, $overrideBaseDir = false)
	{
		$paths = is_array($paths) ? $paths : array($paths);

		foreach ($paths as $path)
		{
			if (false == in_array($paths, $this->templatePaths))
			{
				$this->templatePaths[] = $path;
			}
		}

		return $this;
	}

	/**
	 * Add a function.
	 *
	 * @param   object  $function  @todo not really sure yet..
	 *
	 * @return mixed
	 */
	public function addFunction($function)
	{
		// TODO: Implement addFunction() method.
		echo __METHOD__ . print_r($function, 1);

		return $this;
	}

	/**
	 * Add an extension.
	 *
	 * NOT SUPPORTED !
	 *
	 * @param   object  $extension  The extension.
	 *
	 * @return $this
	 */
	public function addExtension($extension)
	{
		// TODO: Implement addExtension() method.
		// @echo __METHOD__ . '<br /><br />'; //. print_r($extension, 1);
		return $this;
	}
}
