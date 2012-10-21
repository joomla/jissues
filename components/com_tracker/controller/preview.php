<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller class to preview an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerPreview extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		$o       = new stdClass;
		$o->text = $this->input->getHtml('text', '');

		if (!$o->text)
		{
			echo 'Nothing to preview...';
			$this->app->close();
		}

		$params = new JRegistry;
		$params->set('luminous.format', 'html-full');

		JPluginHelper::importPlugin('content');

		JEventDispatcher::getInstance()->trigger('onContentPrepare', array('com_content.article', &$o, $params));

		echo $o->text ? : 'Nothing to preview...';

		$this->app->close();
	}
}
