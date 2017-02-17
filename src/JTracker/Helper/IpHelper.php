<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Helper;

/**
 * Helper class containing methods for working with IP address calculations
 *
 * @since  1.0
 */
abstract class IpHelper
{
	/**
	 * An array of how many addresses are in each CIDR mask
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected static $cidrRanges = [
		0  => 4294967296,
		1  => 2147483648,
		2  => 1073741824,
		3  => 536870912,
		4  => 268435456,
		5  => 134217728,
		6  => 67108864,
		7  => 33554432,
		8  => 16777216,
		9  => 8388608,
		10 => 4194304,
		11 => 2097152,
		12 => 1048576,
		13 => 524288,
		14 => 262144,
		15 => 131072,
		16 => 65536,
		17 => 32768,
		18 => 16382,
		19 => 8192,
		20 => 4096,
		21 => 2048,
		22 => 1024,
		23 => 512,
		24 => 256,
		25 => 128,
		26 => 64,
		27 => 32,
		28 => 16,
		29 => 8,
		30 => 4,
		31 => 2,
		32 => 1,
	];

	/**
	 * Array containing the authorized lookup types for ipInRange()
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected static $authorizedTypes = ['cidr', 'range'];

	/**
	 * Determines if the supplied IP address is in the valid IP range
	 *
	 * @param   string  $testIp    The IP address to test
	 * @param   array   $validIps  The valid IP array, this array may be formatted one of two ways:
	 *                             1) An array containing a list of IPs in CIDR format, e.g. 127.0.0.1/32
	 *                             2) A nested array with each element containing a 'start_range' and 'end_range'
	 * @param   string  $type      The type of addresses submitted, must be 'range' or 'cidr'
	 *
	 * @return  boolean  True if authorized
	 *
	 * @since   1.0
	 */
	public static function ipInRange($testIp, $validIps, $type = 'range')
	{
		// Loop through each element of the array
		foreach ($validIps as $valid)
		{
			$values = static::convertValues($valid, $type);

			// Convert the requestor IP into number format
			$ip = ip2long($testIp);

			// Check if the IP is in our authorised range
			if ($ip >= $values['start'] && $ip <= $values['end'])
			{
				return true;
			}
		}

		// The IP wasn't in range
		return false;
	}

	/**
	 * Converts input data to a numeric value
	 *
	 * @param   array|string  $valid  May be an IP address in CIDR format or an array containing a start and end address
	 * @param   string        $type   The type of value supplied
	 *
	 * @return  array
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	protected static function convertValues($valid, $type)
	{
		switch ($type)
		{
			case 'cidr':
				// Split the CIDR address into a separate IP address and bits
				list($subnet, $bits) = explode('/', $valid);

				// Convert the network address into number format and calculate the end value
				$start = ip2long($subnet);
				$end   = $start + (static::$cidrRanges[(int) $bits] - 1);

				break;

			case 'range':
				// Convert the start_range and end_range values into number format
				$start = ip2long($valid['start_range']);
				$end   = ip2long($valid['end_range']);

				break;

			default:
				// Not supported
				throw new \InvalidArgumentException(
					sprintf(
						'You have supplied an invalid argument for the type parameter.  It must be one of the following: ',
						implode(', ', static::$authorizedTypes)
					)
				);
		}

		return ['start' => $start, 'end' => $end];
	}
}
