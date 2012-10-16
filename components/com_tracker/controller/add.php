<?php
/**
 * User: elkuku
 * Date: 15.10.12
 * Time: 21:57
 */

class TrackerControllerAdd extends TrackerControllerDefault
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
		$this->app->input->set('view', 'add');

		return parent::execute();
	}
}
