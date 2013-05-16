<?php
/**
 * @package     JTracker\Components\Tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Issues;

use Joomla\Factory;
use Joomla\Language\Text;
use Joomla\Tracker\Components\Tracker\HTML\HtmlGitHub;
use Joomla\Tracker\Components\Tracker\Model\IssuesModel;
use Joomla\Tracker\HTML\Html;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * The issues list view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class IssuesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Container for the view's items
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     IssuesModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Pagination object
	 *
	 * @var    JPagination
	 * @since  1.0
	 */
	protected $pagination;

	/**
	 * @var    stdClass
	 * @since  1.0
	 */
	protected $project;

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
		$this->items      = $this->model->getItems();
		//$this->pagination = $this->model->getPagination();
		$this->state      = $this->model->getState();
		$this->project    = $this->model->getProject();

		$this->renderer->set('items',   $this->items);
		$this->renderer->set('state',   $this->state);
		$this->renderer->set('project', $this->project);

		// Prepare the login module
		$this->renderer->set('login', $this->buildLoginModule());

		// Build the toolbar
		//$this->buildToolbar();

		return parent::render();
	}

	/**
	 * Method to build the login module
	 *
	 * @return  string  HTML markup for the login module
	 *
	 * @since   1.0
	 * @todo    Implement this in Twig
	 */
	protected function buildLoginModule()
	{
		$user = Factory::$application->getUser();

		if ($user->id)
		{
			//$output  = HtmlGitHub::avatar($user, 20);
			$output = Html::link('logout', sprintf('Logout %s', $user->username), 'class="btn btn-primary"');
		}
		else
		{
			$output = HtmlGithub::loginButton(Factory::$application->get('github.client_id'));
		}

		return $output;
	}

	/**
	 * Method to build the view's toolbar
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function buildToolbar()
	{
		// Get the user object
		$user = JFactory::getUser();

		// Instantiate the JToolbar object
		$toolbar = JToolbar::getInstance('toolbar');

		if ($this->project)
		{
			// Add a button to submit a new item.
			if ($user->authorise('core.create', 'com_tracker'))
			{
				$toolbar->appendButton('Standard', 'new', 'COM_TRACKER_TOOLBAR_ADD', 'add', false);
			}
		}
	}
}
