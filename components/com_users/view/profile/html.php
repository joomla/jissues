<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * The user profile view
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersViewProfileHtml extends JViewHtml
{
	/**
	 * The data object
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $data;

	/**
	 * The form object
	 *
	 * @var    JForm
	 * @since  1.0
	 */
	protected $form;

	/**
	 * The component params
	 *
	 * @var    JRegistry
	 * @since  1.0
	 */
	protected $params;

	/**
	 * The state object
	 *
	 * @var    JRegistry
	 * @since  1.0
	 */
	protected $state;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     UsersModelProfile
	 * @since   1.0
	 */
	protected $model;

	/**
	 * The page class suffix
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pageclass_sfx = '';

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		if (JFactory::getUser()->guest)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			return '';
		}

		// Get the view data.
		$this->data   = $this->model->getData();
		$this->form   = $this->model->getForm();
		$this->state  = $this->model->getState();
		$this->params = $this->state->get('com_users.params');

		// Check if a user was found.
		if (!$this->data->id)
		{
			JError::raiseError(404, JText::_('JERROR_USERS_PROFILE_NOT_FOUND'));
			return false;
		}

		// Check for layout override
		$active = JFactory::getApplication()->getMenu()->getActive();

		if (isset($active->query['layout']))
		{
			$this->setLayout($active->query['layout']);
		}

		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

		$this->prepareDocument();

		UsersHelperSidebar::prepare();

		return parent::render();
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareDocument()
	{
		/** @var $app JApplicationTracker */
		$app = JFactory::getApplication();
		$menus = $app->getMenu();
		$user = JFactory::getUser();
		$title = null;
		$document = JFactory::getDocument();

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $user->name));
		}
		else
		{
			$this->params->def('page_heading', JText::_('COM_USERS_PROFILE'));
		}

		$title = $this->params->get('page_title', '');
		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
