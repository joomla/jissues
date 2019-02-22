<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

use ElKuKu\G11n\G11n;
use Joomla\DI\Container;
use JTracker\Application;
use JTracker\Helper\LanguageHelper;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension class integrating localization features
 *
 * @since  1.0
 */
class LocalizationExtension extends AbstractExtension implements GlobalsInterface
{
	/**
	 * Application object
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return  array  An array of global variables.
	 *
	 * @since   1.0
	 */
	public function getGlobals()
	{
		return [
			'lang'           => $this->app->getLanguageTag(),
			'languages'      => LanguageHelper::getLanguagesSortedByDisplayName(),
			'languageCodes'  => LanguageHelper::getLanguageCodes(),
			'langDirection'  => LanguageHelper::getDirection($this->app->getLanguageTag()),
		];
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  TwigFunction[]  An array of functions.
	 *
	 * @since   1.0
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction('translate', 'g11n3t'),
			new TwigFunction('_', 'g11n3t'),
			new TwigFunction('g11n4t', 'g11n4t'),
			new TwigFunction('g11n_javascript', [G11n::class, 'getJavaScript'], ['is_safe' => ['html']]),
		];
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return  TwigFilter[]  An array of filters
	 *
	 * @since   1.0
	 */
	public function getFilters()
	{
		return [
			new TwigFilter('_', 'g11n3t'),
		];
	}
}
