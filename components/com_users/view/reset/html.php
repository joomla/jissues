<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Reset view class for Users.
 *
 * @package     Joomla.Site
 * @subpackage  com_users
 * @since       1.5
 */
class UsersViewResetHtml extends JViewHtml
{
	protected $form;

	protected $params;

	protected $state;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     UsersModelReset
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 */
	public function render()
	{
		// This name will be used to get the model
		$name = $this->getLayout();

		// Check that the name is valid - has an associated model.
		if( ! in_array($name, array('confirm', 'complete'))) {
			$name = 'default';
		}

		if ('default' == $name) {
			$formname = 'Form';
		} else {
			$formname = ucfirst($this->name).ucfirst($name).'Form';
		}

		$method = 'get'.$formname;

		// Get the view data.
		$this->form	= $this->model->$method();
		$this->state	= $this->model->getState();
		$this->params	= $this->state->get('com_users.params');

		//Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

		$this->document = JFactory::getDocument();
		$this->prepareDocument();

		return parent::render();
	}

	/**
	 * Prepares the document.
	 *
	 * @since	1.6
	 */
	protected function prepareDocument()
	{
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu();
		$title 		= null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', JText::_('COM_USERS_RESET'));
		}

		$title = $this->params->get('page_title', '');
		if (empty($title)) {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
