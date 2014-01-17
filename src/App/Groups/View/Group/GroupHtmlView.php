<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\View\Group;

use App\Groups\Model\GroupModel;

use JTracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * The group edit view
 *
 * @since  1.0
 */
class GroupHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     GroupModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		// Set the vars to the template.
		$this->renderer->set('group', ArrayHelper::fromObject($this->model->getItem()));
		$this->renderer->set('project', $this->getProject());

		return parent::render();
	}
}
