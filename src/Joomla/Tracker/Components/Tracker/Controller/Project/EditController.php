<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Project;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;
use Joomla\Tracker\Components\Tracker\Controller\DefaultController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class EditController extends DefaultController
{
	protected $defaultView = 'project';

	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since  1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the default view
		$this->getInput()->set('layout', 'project.edit');
	}
}
