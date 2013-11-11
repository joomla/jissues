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
use JTracker\Container;

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
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 */
	public function render()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$alias = $application->input->getCmd('alias');

		if ($alias)
		{
			try
			{
				$item = $this->model->getItem($alias);
				$this->renderer->set('page', $item->getIterator());

				$this->renderer->set(
					'editLink',
					'https://github.com/joomla/jissues/edit/framework/Documentation/'
					. substr($alias, 4) . '.md'
				);
			}
			catch (\RuntimeException $e)
			{
				throw new RoutingException($alias);
			}
		}
		else
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
}
