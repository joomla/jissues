<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\Helper;

use JTracker\Helper\IpHelper;

/**
 * Test class for \JTracker\Helper\IpHelper
 *
 * @since  1.0
 */
class IpHelperTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test cases for the testIpInRange method
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestIpInRange()
	{
		return array(
			// Element order: result, test address, range element, type
			array(
				true,
				'192.168.0.32',
				array(
					array(
						'start_range' => '192.168.0.0',
						'end_range'   => '192.168.0.255',
					)
				),
				'range'
			),
			array(
				false,
				'192.168.1.32',
				array(
					array(
						'start_range' => '192.168.0.0',
						'end_range'   => '192.168.0.255',
					)
				),
				'range'
			),
			array(
				true,
				'192.168.1.32',
				array(
					array(
						'start_range' => '192.168.0.0',
						'end_range'   => '192.168.0.255',
					),
					array(
						'start_range' => '192.168.1.0',
						'end_range'   => '192.168.1.255',
					)
				),
				'range'
			),
			array(
				false,
				'192.168.1.32',
				array(
					array(
						'start_range' => '192.168.0.0',
						'end_range'   => '192.168.0.255',
					),
					array(
						'start_range' => '192.168.2.0',
						'end_range'   => '192.168.2.255',
					)
				),
				'range'
			),
			array(
				true,
				'192.168.0.32',
				array(
					'192.168.0.0/24'
				),
				'cidr'
			),
			array(
				false,
				'192.168.0.32',
				array(
					'192.168.0.0/28'
				),
				'cidr'
			),
			array(
				true,
				'192.168.3.32',
				array(
					'192.168.0.0/24',
					'192.168.1.128/25',
					'192.168.2.0/23'
				),
				'cidr'
			),
			array(
				false,
				'192.168.1.32',
				array(
					'192.168.0.0/24',
					'192.168.1.128/25',
					'192.168.2.0/23'
				),
				'cidr'
			),
		);
	}

	/**
	 * Tests the \JTracker\Helper\IpHelper::ipInRange method.
	 *
	 * @param   string  $result    Expected test result
	 * @param   string  $testIp    The IP address to test
	 * @param   array   $validIps  The valid IP array, this array may be formatted one of two ways:
	 *                             1) An array containing a list of IPs in CIDR format, e.g. 127.0.0.1/32
	 *                             2) A nested array with each element containing a 'start_range' and 'end_range'
	 * @param   string  $type      The type of addresses submitted, must be 'range' or 'cidr'
	 *
	 * @return  void
	 *
	 * @covers        \JTracker\Helper\IpHelper::ipInRange
	 * @covers        \JTracker\Helper\IpHelper::convertValues
	 * @dataProvider  dataTestIpInRange
	 * @since         1.0
	 */
	public function testIpInRange($result, $testIp, $validIps, $type = 'range')
	{
		$this->assertEquals(
			IpHelper::ipInRange($testIp, $validIps, $type),
			$result
		);
	}

	/**
	 * Tests the \JTracker\Helper\IpHelper::ipInRange method for a bad type parameter
	 *
	 * @return  void
	 *
	 * @covers             \JTracker\Helper\IpHelper::ipInRange
	 * @covers             \JTracker\Helper\IpHelper::convertValues
	 * @expectedException  \InvalidArgumentException
	 * @since              1.0
	 */
	public function testIpInRangeException()
	{
		IpHelper::ipInRange('192.168.0.32', array('192.168.0.0/24'), 'decimal');
	}
}
