<?php
/**
 * @package     JTracker
 * @subpackage  GitHub
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * GitHub API Meta class.
 *
 * @package     JTracker
 * @subpackage  GitHub
 * @since       1.0
 */
class JGithubMeta extends JGithubObject
{
	/**
	 * Method to get the authorized IP addresses for services
	 *
	 * @return  array  Authorized IP addresses
	 *
	 * @since   1.0
	 * @throws  DomainException
	 */
	public function getMeta()
	{
		// Build the request path.
		$path = '/meta';

		// Send the request.
		$response = $this->client->get($this->fetchUrl($path));

		// Validate the response code.
		if ($response->code != 200)
		{
			// Decode the error response and throw an exception.
			$error = json_decode($response->body);
			throw new DomainException($error->message, $response->code);
		}

		/*
		 * The response body returns the IP addresses in CIDR format
		 * Decode the response body and strip the subnet mask information prior to
		 * returning the data to the user.  We're assuming quite a bit here that all
		 * masks will be /32 as they are as of the time of development.
		 */

		$authorizedIps = array();
		$githubIps     = json_decode($response->body);

		foreach ($githubIps as $key => $serviceIps)
		{
			// The first level contains an array of IPs based on the service
			$authorizedIps[$key] = array();

			foreach ($serviceIps as $serviceIp)
			{
				// The second level is each individual IP address, strip the mask here
				$authorizedIps[$key][] = substr($serviceIp, 0, -3);
			}
		}

		return $authorizedIps;
	}
}
