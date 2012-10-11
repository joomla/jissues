<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('script', 'system/core.js', false, true);

/**
 * The issues list view
 *
 * @package     BabDev.Tracker
 * @subpackage  View
 * @since       1.0
 */
class TrackerViewIssuesHtml extends JViewHtml
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     TrackerModelIssues
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	public function render()
	{
		$app = JFactory::getApplication();

		// Register the document
		$this->document = $app->getDocument();

		$this->items      = $this->model->getItems();
		$this->pagination = $this->model->getPagination();
		$this->state      = $this->model->getState();

		return parent::render();
	}
}
