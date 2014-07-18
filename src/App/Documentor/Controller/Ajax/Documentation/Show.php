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
	 * Model object
	 *
	 * @var    \JTracker\Model\AbstractDoctrineItemModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Prepare the response.
	 *
	 * @throws \RuntimeException
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
			$pageObject = $this->model->findOneBy(['page' => $page, 'path' => $path]);

			if (!$pageObject)
			{
				throw new \RuntimeException('Invalid page');
			}

			$text = $pageObject->getText();
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
