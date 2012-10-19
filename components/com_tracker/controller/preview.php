<?php
/**
 * User: elkuku
 * Date: 14.10.12
 * Time: 00:20
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
	 * @since            12.1
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		$o       = new stdClass;
		$o->text = JFactory::getApplication()->input->get('text', '', 'html');

		if (!$o->text)
		{
			echo 'Nothing to preview...';

			jexit();
		}

		$params = new JRegistry;
		$params->set('luminous.format', 'html-full');

		JPluginHelper::importPlugin('content');

		JEventDispatcher::getInstance()
			->trigger('onContentPrepare', array('com_content.article', &$o, $params));

		echo $o->text ? : 'Nothing to preview...';

		jexit();
	}
}
