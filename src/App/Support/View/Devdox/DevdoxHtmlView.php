<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Support\View\Devdox;

use App\Support\Model\DefaultModel;

use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The developer documentation view
 *
 * @since  1.0
 */
class DevdoxHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The model object.
	 *
	 * @var    DefaultModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * The page alias.
	 *
	 * @var string
	 * @since   1.0
	 */
	protected $alias = '';

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 */
	public function render()
	{
		try
		{
			$alias = $this->getAlias();

			$item = $this->model->getItem($alias);
			$this->renderer->set('page', $item->getIterator());
		}
		catch (\RuntimeException $e)
		{
			$pagePrefix = 'dox-';
			$pages      = array();

			/* @type \DirectoryIterator $file */
			foreach (new \DirectoryIterator(JPATH_ROOT . '/Documentation') as $file)
			{
				if ($file->isDot())
				{
					continue;
				}

				$name = substr($file->getFilename(), 0, strrpos($file->getFilename(), '.'));

				$pages[$name] = $pagePrefix . $name;

				uksort(
					$pages, function ($a, $b)
					{
						return strcasecmp($a, $b);
					}
				);
			}

			$this->renderer->set('pages', $pages);
		}

		return parent::render();
	}

	/**
	 * Get the page alias.
	 *
	 * @return string
	 *
	 * @since   1.0
	 * @throws \RuntimeException
	 */
	public function getAlias()
	{
		if ('' == $this->alias)
		{
			throw new \RuntimeException('Alias not set.');
		}

		return $this->alias;
	}

	/**
	 * Set the page alias.
	 *
	 * @param   string  $alias  The page alias.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}
}
