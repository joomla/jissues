<?php
/**
 * Part of the Joomla Tracker Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Nicolas Dupont <nicolas@akeneo.com>. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace JTracker\Crowdin;

use Akeneo\Crowdin\Client as CrowdinClient;

/**
 * Simple Crowdin PHP client
 *
 * @author  Nicolas Dupont <nicolas@akeneo.com>
 * @since   1.0
 */
class Client extends CrowdinClient
{
	/**
	 * Get an API
	 *
	 * @param   string  $method  the api method
	 *
	 * @return mixed
	 */
	public function api($method)
	{
		switch ($method)
		{
			case 'export-file':
				$api = new Api\ExportFile($this);
				break;
			default:
				return parent::api($method);
				break;
		}

		return $api;
	}
}
