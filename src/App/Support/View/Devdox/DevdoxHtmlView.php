<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Support\View\Devdox;

use App\Support\Model\DefaultModel;
use Joomla\Factory;
use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The issues item view
 *
 * @since  1.0
 */
class DevdoxHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * @var  DefaultModel
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 * @return  string  The rendered view.
	 */
	public function render()
	{
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$alias = $application->input->getCmd('alias');

		if ($alias)
		{
			try
			{
				$item = $this->model->getItem($alias);
				$this->renderer->set('page', $item->getIterator());
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
