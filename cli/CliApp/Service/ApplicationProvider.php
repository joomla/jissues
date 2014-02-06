<?php
/**
 * Part of the Joomla Tracker CLI Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Service;

use Joomla\Application\AbstractApplication;
use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container as JoomlaContainer;

/**
 * Class Application service.
 *
 * @since  1.0
 */
class ApplicationProvider implements ServiceProviderInterface
{
	/**
	 * Constructor
	 *
	 * @param   AbstractApplication  $app  Application instance
	 *
	 * @since   1.0
	 */
	public function __construct(AbstractApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   JoomlaContainer  $container  The DI container.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function register(JoomlaContainer $container)
	{
		$container->set('app',
			function ()
			{
				return $this->app;
			}, true, true
		);
	}
}
