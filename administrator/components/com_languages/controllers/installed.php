<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Languages Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 * @since       1.5
 */
class LanguagesControllerInstalled extends JControllerLegacy
{
	/**
	 * task to set the default language
	 */
	public function setDefault()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JInvalid_Token'));

		$cid = $this->input->get('cid', '');
		$model = $this->getModel('installed');
		if ($model->publish($cid))
		{
			$msg = JText::_('COM_LANGUAGES_MSG_DEFAULT_LANGUAGE_SAVED');
			$type = 'message';
		}
		else
		{
			$msg = $this->getError();
			$type = 'error';
		}

		$clientId = $model->getState('filter.client_id');
		$this->setredirect('index.php?option=com_languages&view=installed&client='.$clientId, $msg, $type);
	}
}
