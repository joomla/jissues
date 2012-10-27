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
 * The user login view
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersViewLoginHtml extends JViewHtml
{
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
	 * The user object
	 *
	 * @var    JUser
	 * @since  1.0
	 */
	protected $user;

	/**
	 * The page class suffix
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pageclass_sfx = '';

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var    UsersModelLogin
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		// Get the view data.
		$this->user = JFactory::getUser();
		$this->form = $this->model->getForm();
		$this->state = $this->model->getState();
		$this->params = $this->state->get('com_users.params');

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

		$this->prepareDocument();

		UsersHelperSidebar::prepare();

		return parent::render();
	}

	/**
	 * Prepares the document
	 *
	 * @return  UsersViewLoginHtml
	 *
	 * @since   1.0
	 */
	protected function prepareDocument()
	{
		/** @var $app JApplicationTracker */
		$app = JFactory::getApplication();
		$menus = $app->getMenu();
		$user = JFactory::getUser();
		$login = $user->get('guest') ? true : false;
		$title = null;
		$document = JFactory::getDocument();
		$title = $this->params->get('page_title', '');

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', $login ? JText::_('JLOGIN') : JText::_('JLOGOUT'));
		}

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

		return $this;
	}
}
