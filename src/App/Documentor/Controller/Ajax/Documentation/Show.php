<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Documentor\Controller\Ajax\Documentation;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to view documentation pages.
 *
 * @since  1.0
 */
class Show extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		ob_start();

		/* @type $input \Joomla\Input\Input */
		$input = $this->getContainer()->get('app')->input;

		$path = $input->getPath('path', '');
		$page = $input->getCmd('page');
		$text = '';

		if ($page)
		{
			$entityManager = $this->getContainer()->get('EntityManager');

			$text = $entityManager->getRepository('App\Documentor\Entity\Document')
				->findOneBy(['page' => $page, 'path' => $path])
				->getText();
		}

		$this->response->editLink = 'https://github.com/joomla/jissues/edit/master/Documentation/' . ($path ? $path . '/' : '') . $page . '.md';
		$this->response->permaLink = '/documentation/view/?page=' . $page . ($path ? '&path=' . $path : '');

		$err = ob_get_clean();

		if ($err)
		{
			// @todo better error handling...
			$this->response->data = $err;
		}
		else
		{
			$this->response->data = $text;
		}

		return;
	}
}
