<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller;

use App\Groups\Model\GroupModel;
use App\Tracker\Controller\DefaultController;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

/**
 * Controller class to manage a user group.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class GroupController extends DefaultController
{
	/**
	 * @var  GroupModel
	 */
	protected $model;

	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the default view
		$input->set('view', 'group');
		$input->set('layout', 'edit');
	}

	public function initialize()
	{
		parent::initialize();

		$this->model->setGroupId($this->container->get('app')->input->getInt('group_id'));
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('manage');

		return parent::execute();
	}
}
