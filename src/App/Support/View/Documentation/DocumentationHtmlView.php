<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\View\Documentation;

use App\Support\Model\DefaultModel;
use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The developer documentation view
 *
 * @since  1.0
 */
class DocumentationHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The model object.
	 *
	 * @var    DefaultModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Full path string. e.g. path=path/to&page=page..
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $fullPath = '';

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
		$this->renderer->set('fullPath', $this->fullPath);

		return parent::render();
	}

	/**
	 * Set the page alias.
	 *
	 * @param   string  $fullPath  Full path string. e.g. path=path/to&page=page.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setFullPath($fullPath)
	{
		$this->fullPath = $fullPath;

		return $this;
	}
}
