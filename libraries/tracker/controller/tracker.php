<?php
/**
 * @package     JTracker
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Generic Joomla! Issue Tracker Controller class
 *
 * @package     JTracker
 * @subpackage  Controller
 * @since       1.0
 */
abstract class JControllerTracker extends JControllerBase
{
	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   string  $option  The component to check the permissions of
	 * @param   array   $data    An array of input data.
	 * @param   string  $key     The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	protected function allowEdit($option, $data = array(), $key = 'id')
	{
		return JFactory::getUser()->authorise('core.edit', $option);
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.0
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$tmpl   = $this->input->get('tmpl');
		$layout = $this->input->get('layout', 'edit');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout)
		{
			$append .= '&layout=' . $layout;
		}

		if ($recordId)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.0
	 */
	protected function getRedirectToListAppend()
	{
		$tmpl = $this->input->get('tmpl');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		return $append;
	}

	/**
	 * Method to add a record ID to the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function holdEditId($context, $id)
	{
		$app = $this->getApplication();
		$values = (array) $app->getUserState($context . '.id');

		// Add the id to the list if non-zero.
		if (!empty($id))
		{
			array_push($values, (int) $id);
			$values = array_unique($values);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				JLog::add(
					sprintf(
						'Holding edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					),
					JLog::INFO,
					'controller'
				);
			}
		}
	}
}
