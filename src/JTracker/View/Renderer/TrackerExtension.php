<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

use Adaptive\Diff\Diff;

use App\Tracker\DiffRenderer\Html\Inline;

use ElKuKu\G11n\G11n;

use Joomla\Cache\Item\Item;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Http\HttpFactory;

use JTracker\Application;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Helper\LanguageHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Asset\Packages;

/**
 * Twig extension class
 *
 * @since  1.0
 */
class TrackerExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
	/**
	 * Application object
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Cache pool
	 *
	 * @var    CacheItemPoolInterface
	 * @since  1.0
	 */
	private $cache;

	/**
	 * Database connector
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $db;

	/**
	 * Login helper
	 *
	 * @var    GitHubLoginHelper
	 * @since  1.0
	 */
	private $loginHelper;

	/**
	 * Packages object to look up asset paths
	 *
	 * @var    Packages
	 * @since  1.0
	 */
	private $packages;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		$this->app         = $container->get('app');
		$this->cache       = $container->get('cache');
		$this->db          = $container->get('db');
		$this->loginHelper = $container->get(GitHubLoginHelper::class);
		$this->packages    = $container->get(Packages::class);
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
			'uri'            => $this->app->get('uri'),
			'offset'         => $this->app->getUser()->params->get('timezone') ?: $this->app->get('system.offset'),
			'useCDN'         => $this->app->get('system.use_cdn'),
			'templateDebug'  => $this->app->get('debug.template', false),
			'jdebug'         => JDEBUG,
			'lang'           => $this->app->getLanguageTag(),
			'languages'      => LanguageHelper::getLanguagesSortedByDisplayName(),
			'languageCodes'  => LanguageHelper::getLanguageCodes(),
			'langDirection'  => LanguageHelper::getDirection($this->app->getLanguageTag()),
			'g11nJavaScript' => G11n::getJavaScript(),
			'loginUrl'       => $this->loginHelper->getLoginUri()
		];
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  \Twig_Function[]  An array of functions.
	 *
	 * @since   1.0
	 */
	public function getFunctions()
	{
		$functions = [
			new \Twig_Function('translate', 'g11n3t'),
			new \Twig_Function('_', 'g11n3t'),
			new \Twig_Function('g11n4t', 'g11n4t'),
			new \Twig_Function('sprintf', 'sprintf'),
			new \Twig_Function('stripJRoot', [$this, 'stripJRoot']),
			new \Twig_Function('asset', [$this, 'getAssetUrl']),
			new \Twig_Function('avatar', [$this, 'fetchAvatar']),
			new \Twig_Function('prioClass', [$this, 'getPrioClass']),
			new \Twig_Function('priorities', [$this, 'getPriorities']),
			new \Twig_Function('getPriority', [$this, 'getPriority']),
			new \Twig_Function('status', [$this, 'getStatus']),
			new \Twig_Function('getStatuses', [$this, 'getStatuses']),
			new \Twig_Function('translateStatus', [$this, 'translateStatus']),
			new \Twig_Function('relation', [$this, 'getRelation']),
			new \Twig_Function('issueLink', [$this, 'issueLink']),
			new \Twig_Function('getRelTypes', [$this, 'getRelTypes']),
			new \Twig_Function('getRelType', [$this, 'getRelType']),
			new \Twig_Function('getTimezones', [$this, 'getTimezones']),
			new \Twig_Function('getContrastColor', [$this, 'getContrastColor']),
			new \Twig_Function('renderDiff', [$this, 'renderDiff']),
			new \Twig_Function('renderLabels', [$this, 'renderLabels']),
			new \Twig_Function('arrayDiff', [$this, 'arrayDiff']),
			new \Twig_Function('userTestOptions', [$this, 'getUserTestOptions']),
			new \Twig_Function('cdn_footer', [$this, 'getCdnFooter']),
			new \Twig_Function('cdn_menu', [$this, 'getCdnMenu']),
			new \Twig_Function('datepicker_locale_js', [$this, 'getDatepickerLocaleJs']),
			new \Twig_Function('datepicker_locale_code', [$this, 'getDatepickerLocaleCode']),
		];

		if (!JDEBUG)
		{
			array_push($functions, new \Twig_Function('dump', [$this, 'dump']));
		}

		return $functions;
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return  \Twig_Filter[]  An array of filters
	 *
	 * @since   1.0
	 */
	public function getFilters()
	{
		return [
			new \Twig_Filter('basename', 'basename'),
			new \Twig_Filter('get_class', 'get_class'),
			new \Twig_Filter('json_decode', 'json_decode'),
			new \Twig_Filter('stripJRoot', [$this, 'stripJRoot']),
			new \Twig_Filter('contrastColor', [$this, 'getContrastColor']),
			new \Twig_Filter('labels', [$this, 'renderLabels']),
			new \Twig_Filter('yesno', [$this, 'yesNo']),
			new \Twig_Filter('_', 'g11n3t'),
			new \Twig_Filter('mergeStatus', [$this, 'getMergeStatus']),
			new \Twig_Filter('mergeBadge', [$this, 'renderMergeBadge']),
		];
	}

	/**
	 * Replaces the Joomla! root path defined by the constant "JPATH_ROOT" with the string "JROOT".
	 *
	 * @param   string  $string  The string to process.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public function stripJRoot($string)
	{
		return str_replace(JPATH_ROOT, 'JROOT', $string);
	}

	/**
	 * Fetch an avatar.
	 *
	 * @param   string   $userName  The user name.
	 * @param   integer  $width     The with in pixel.
	 * @param   string   $class     The class.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @todo    Refactor avatar paths to use the media directory
	 */
	public function fetchAvatar($userName = '', $width = 0, $class = '')
	{
		$base = $this->app->get('uri.base.path');

		$avatar = $userName ? $userName . '.png' : 'user-default.png';

		$width = $width ? ' style="width: ' . $width . 'px"' : '';
		$class = $class ? ' class="' . $class . '"' : '';

		return '<img'
		. $class
		. ' alt="avatar ' . $userName . '"'
		. ' src="' . $base . 'images/avatars/' . $avatar . '"'
		. $width
		. ' />';
	}

	/**
	 * Fetches and renders the CDN footer element, optionally caching the data.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getCdnFooter()
	{
		$key = md5(get_class($this) . '::' . __METHOD__ . ' - language - ' . $this->app->getLanguageTag());

		$fetchPage = function ()
		{
			// Set a very short timeout to try and not bring the site down
			$response = HttpFactory::getHttp()->get(
				'https://cdn.joomla.org/template/renderer.php?section=footer&language=' . $this->app->getLanguageTag(),
				[],
				2
			);

			if ($response->code !== 200)
			{
				return g11n3t('Could not load template section.');
			}

			$body = $response->body;

			// Replace the common placeholders
			$body = strtr(
				$body,
				[
					'%reportroute%'  => $this->app->get('uri.base.path') . 'tracker/jtracker/add',
					'%currentyear%' => date('Y'),
				]
			);

			// Replace the context aware placeholders
			if ($this->app->getUser()->id)
			{
				$body = strtr(
					$body,
					[
						'%loginroute%' => $this->app->get('uri.base.path') . 'logout',
						'%logintext%'  => g11n3t('Log out'),
					]
				);
			}
			else
			{
				$body = strtr(
					$body,
					[
						'%loginroute%' => $this->loginHelper->getLoginUri(),
						'%logintext%'  => g11n3t('Log in'),
					]
				);
			}

			return $body;
		};

		if ($this->app->get('cache.enabled', false))
		{
			if ($this->cache->hasItem($key))
			{
				$item = $this->cache->getItem($key);

				// Make sure we got a hit on the item, otherwise we'll have to re-cache
				if ($item->isHit())
				{
					$body = $item->get();
				}
				else
				{
					try
					{
						$body = $fetchPage();

						$item = (new Item($key, $this->app->get('cache.lifetime', 900)))
							->set($body);

						$this->cache->save($item);
					}
					catch (\RuntimeException $e)
					{
						$body = 'Could not load template section.';
					}
				}
			}
			else
			{
				try
				{
					$body = $fetchPage();

					$item = (new Item($key, $this->app->get('cache.lifetime', 900)))
						->set($body);

					$this->cache->save($item);
				}
				catch (\RuntimeException $e)
				{
					$body = 'Could not load template section.';
				}
			}
		}
		else
		{
			try
			{
				$body = $fetchPage();
			}
			catch (\RuntimeException $e)
			{
				$body = 'Could not load template section.';
			}
		}

		return $body;
	}

	/**
	 * Fetches and renders the CDN menu element, optionally caching the data.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getCdnMenu()
	{
		$key = md5(get_class($this) . '::' . __METHOD__ . ' - language - ' . $this->app->getLanguageTag());

		$fetchPage = function ()
		{
			// Set a very short timeout to try and not bring the site down
			$response = HttpFactory::getHttp()->get(
				'https://cdn.joomla.org/template/renderer.php?section=menu&language=' . $this->app->getLanguageTag(),
				[],
				2
			);

			if ($response->code !== 200)
			{
				return g11n3t('Could not load template section.');
			}

			$body = $response->body;

			// Needless concatenation for PHPCS 150 character line limits...
			$replace = "\t<div id=\"nav-search\" class=\"navbar-search pull-right\">\n\t\t"
				. "<jdoc:include type=\"modules\" name=\"position-0\" style=\"none\" />\n\t</div>\n";

			// Remove the search module
			$body = str_replace($replace, '', $body);

			return $body;
		};

		if ($this->app->get('cache.enabled', false))
		{
			if ($this->cache->hasItem($key))
			{
				$item = $this->cache->getItem($key);

				// Make sure we got a hit on the item, otherwise we'll have to re-cache
				if ($item->isHit())
				{
					$body = $item->get();
				}
				else
				{
					try
					{
						$body = $fetchPage();

						$item = (new Item($key, $this->app->get('cache.lifetime', 900)))
							->set($body);

						$this->cache->save($item);
					}
					catch (\RuntimeException $e)
					{
						$body = 'Could not load template section.';
					}
				}
			}
			else
			{
				try
				{
					$body = $fetchPage();

					$item = (new Item($key, $this->app->get('cache.lifetime', 900)))
						->set($body);

					$this->cache->save($item);
				}
				catch (\RuntimeException $e)
				{
					$body = 'Could not load template section.';
				}
			}
		}
		else
		{
			try
			{
				$body = $fetchPage();
			}
			catch (\RuntimeException $e)
			{
				$body = 'Could not load template section.';
			}
		}

		return $body;
	}

	/**
	 * Get a CSS class according to the item priority.
	 *
	 * @param   integer  $priority  The priority
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getPrioClass($priority)
	{
		switch ($priority)
		{
			case 1 :
				return 'badge-important';

			case 2 :
				return 'badge-warning';

			case 3 :
				return 'badge-info';

			case 4 :
				return 'badge-inverse';

			default :
				return '';
		}
	}

	/**
	 * Get a text list of issue priorities.
	 *
	 * @return  array  The list of priorities.
	 *
	 * @since   1.0
	 */
	public function getPriorities()
	{
		return [
			1 => g11n3t('Critical'),
			2 => g11n3t('Urgent'),
			3 => g11n3t('Medium'),
			4 => g11n3t('Low'),
			5 => g11n3t('Very low'),
		];
	}

	/**
	 * Get the priority text.
	 *
	 * @param   integer  $id  The priority id.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	public function getPriority($id)
	{
		$priorities = $this->getPriorities();

		return isset($priorities[$id]) ? $priorities[$id] : 'N/A';
	}

	/**
	 * Dummy function to prevent throwing exception on dump function in the non-debug mode.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function dump()
	{
		return;
	}

	/**
	 * Retrieves a human friendly relationship for a given type
	 *
	 * @param   string  $relation  Relation type
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getRelation($relation)
	{
		$relations = [
			'duplicate_of' => g11n3t('Duplicate of'),
			'related_to'   => g11n3t('Related to'),
			'not_before'   => g11n3t('Not before'),
			'pr_for'       => g11n3t('Pull Request for'),
		];

		return $relations[$relation] ?? '';
	}

	/**
	 * Get a status object based on its id.
	 *
	 * @param   integer  $id  The id
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getStatus($id)
	{
		static $statuses = [];

		if (!$statuses)
		{
			$items = $this->db->setQuery(
				$this->db->getQuery(true)
					->from($this->db->quoteName('#__status'))
					->select('*')
			)->loadObjectList();

			foreach ($items as $status)
			{
				$status->cssClass = $status->closed ? 'error' : 'success';
				$statuses[$status->id] = $status;
			}
		}

		if (!array_key_exists($id, $statuses))
		{
			throw new \UnexpectedValueException('Unknown status id:' . (int) $id);
		}

		return $statuses[$id];
	}

	/**
	 * Get a text list of statuses.
	 *
	 * @param   int  $state  The state of issue: 0 - open, 1 - closed.
	 *
	 * @return  array  The list of statuses.
	 *
	 * @since   1.0
	 */
	public function getStatuses($state = null)
	{
		switch ((string) $state)
		{
			case '0':
				$statuses = [
					1 => g11n3t('New'),
					2 => g11n3t('Confirmed'),
					3 => g11n3t('Pending'),
					4 => g11n3t('Ready To Commit'),
					6 => g11n3t('Needs Review'),
					7 => g11n3t('Information Required'),
					14 => g11n3t('Discussion'),
				];
				break;

			case '1':
				$statuses = [
					5 => g11n3t('Fixed in Code Base'),
					8 => g11n3t('Unconfirmed Report'),
					9 => g11n3t('No Reply'),
					10 => g11n3t('Closed'),
					11 => g11n3t('Expected Behaviour'),
					12 => g11n3t('Known Issue'),
					13 => g11n3t('Duplicate Report'),
				];
				break;

			default:
				$statuses = [
					1 => g11n3t('New'),
					2 => g11n3t('Confirmed'),
					3 => g11n3t('Pending'),
					4 => g11n3t('Ready To Commit'),
					6 => g11n3t('Needs Review'),
					7 => g11n3t('Information Required'),
					14 => g11n3t('Discussion'),
					5 => g11n3t('Fixed in Code Base'),
					8 => g11n3t('Unconfirmed Report'),
					9 => g11n3t('No Reply'),
					10 => g11n3t('Closed'),
					11 => g11n3t('Expected Behaviour'),
					12 => g11n3t('Known Issue'),
					13 => g11n3t('Duplicate Report'),
				];
		}

		return $statuses;
	}

	/**
	 * Retrieves the translated status name for a given ID
	 *
	 * @param   integer  $id  Status ID
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function translateStatus($id)
	{
		$statuses = $this->getStatuses();

		return $statuses[$id];
	}

	/**
	 * Get a contrasting color (black or white).
	 *
	 * http://24ways.org/2010/calculating-color-contrast/
	 *
	 * @param   string  $hexColor  The hex color.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getContrastColor($hexColor)
	{
		$r = hexdec(substr($hexColor, 0, 2));
		$g = hexdec(substr($hexColor, 2, 2));
		$b = hexdec(substr($hexColor, 4, 2));
		$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($yiq >= 128) ? 'black' : 'white';
	}

	/**
	 * Render a list of labels.
	 *
	 * @param   string  $idsString  Comma separated list of IDs.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderLabels($idsString)
	{
		static $labels;

		if (!$labels)
		{
			$labels = $this->app->getProject()->getLabels();
		}

		$html = [];

		$ids = ($idsString) ? explode(',', $idsString) : [];

		foreach ($ids as $id)
		{
			if (array_key_exists($id, $labels))
			{
				$bgColor = $labels[$id]->color;
				$color   = $this->getContrastColor($bgColor);
				$name    = $labels[$id]->name;
			}
			else
			{
				$bgColor = '000000';
				$color   = 'ffffff';
				$name    = '?';
			}

			$html[] = '<span class="label" style="background-color: #' . $bgColor . '; color: ' . $color . ';">';
			$html[] = $name;
			$html[] = '</span>';
		}

		return implode("\n", $html);
	}

	/**
	 * Get HTML for an issue link.
	 *
	 * @param   integer  $number  Issue number.
	 * @param   boolean  $closed  Issue closed status.
	 * @param   string   $title   Issue title.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function issueLink($number, $closed, $title = '')
	{
		$html = [];

		$title = ($title) ? : ' #' . $number;
		$href = $this->app->get('uri')->base->path
			. 'tracker/' . $this->app->getProject()->alias . '/' . $number;

		$html[] = '<a href="' . $href . '" title="' . $title . '">';
		$html[] = $closed ? '<del># ' . $number . '</del>' : '# ' . $number;
		$html[] = '</a>';

		return implode("\n", $html);
	}

	/**
	 * Get relation types.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getRelTypes()
	{
		static $relTypes = [];

		if (!$relTypes)
		{
			$relTypes = $this->db->setQuery(
				$this->db->getQuery(true)
					->from($this->db->quoteName('#__issues_relations_types'))
					->select($this->db->quoteName('id', 'value'))
					->select($this->db->quoteName('name', 'text'))
			)->loadObjectList();
		}

		return $relTypes;
	}

	/**
	 * Get the relation type text.
	 *
	 * @param   integer  $id  The relation id.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getRelType($id)
	{
		foreach ($this->getRelTypes() as $relType)
		{
			if ($relType->value == $id)
			{
				return $this->getRelation($relType->text);
			}
		}

		return '';
	}

	/**
	 * Generate a localized yes/no message.
	 *
	 * @param   integer  $value  A value that evaluates to TRUE or FALSE.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function yesNo($value)
	{
		return $value ? g11n3t('Yes') : g11n3t('No');
	}

	/**
	 * Get the timezones.
	 *
	 * @return  array  The timezones.
	 *
	 * @since   1.0
	 */
	public function getTimezones()
	{
		return \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
	}

	/**
	 * Generate HTML output for a "merge status badge".
	 *
	 * @param   string  $status  The merge status.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function renderMergeBadge($status)
	{
		switch ($status)
		{
			case 'success':
				$class = 'success';
				break;
			case 'pending':
				$class = 'warning';
				break;
			case 'error':
			case 'failure':
				$class = 'important';
				break;

			default:
				throw new \RuntimeException('Unknown status: ' . $status);
		}

		return '<span class="badge badge-' . $class . '">' . $this->getMergeStatus($status) . '</span>';
	}

	/**
	 * Generate a translated merge status.
	 *
	 * @param   string  $status  The merge status.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getMergeStatus($status)
	{
		switch ($status)
		{
			case 'success':
				return g11n3t('Success');

			case 'pending':
				return g11n3t('Pending');

			case 'error':
				return g11n3t('Error');

			case 'failure':
				return g11n3t('Failure');
		}

		throw new \RuntimeException('Unknown status: ' . $status);
	}

	/**
	 * Render the differences between two text strings.
	 *
	 * @param   string   $old              The "old" text.
	 * @param   string   $new              The "new" text.
	 * @param   boolean  $showLineNumbers  To show line numbers.
	 * @param   boolean  $showHeader       To show the table header.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderDiff($old, $new, $showLineNumbers = true, $showHeader = true)
	{
		$options = [];

		$renderer = (new Inline)
			->setShowLineNumbers($showLineNumbers)
			->setShowHeader($showHeader);

		return (new Diff(explode("\n", $old), explode("\n", $new), $options))->render($renderer);
	}

	/**
	 * Get the difference of two comma separated value strings.
	 *
	 * @param   string  $a  The "a" string.
	 * @param   string  $b  The "b" string.
	 *
	 * @return string  difference values comma separated
	 *
	 * @since   1.0
	 */
	public function arrayDiff($a, $b)
	{
		$as = explode(',', $a);
		$bs = explode(',', $b);

		return implode(',', array_diff($as, $bs));
	}

	/**
	 * Get a user test option string.
	 *
	 * @param   integer  $id  The option ID.
	 *
	 * @return  mixed array or string if an ID is given.
	 *
	 * @since   1.0
	 */
	public function getUserTestOptions($id = null)
	{
		static $options = [];

		$options = $options ? : [
			0 => g11n3t('Not tested'),
			1 => g11n3t('Tested successfully'),
			2 => g11n3t('Tested unsuccessfully'),
		];

		return ($id !== null && array_key_exists($id, $options)) ? $options[$id] : $options;
	}

	/**
	 * Get the URI for an asset
	 *
	 * @param   string  $path         A public path
	 * @param   string  $packageName  The name of the asset package to use
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getAssetUrl($path, $packageName = null)
	{
		return $this->packages->getUrl($path, $packageName);
	}

	/**
	 * Returns the public URL for the Bootstrap Datepicker locale
	 *
	 * @param   string  $locale  The locale to load
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getDatepickerLocaleJs($locale)
	{
		$localePathSegment = 'js/vendor/bootstrap-datepicker/locales/bootstrap-datepicker.%locale%.min.js';
		$basePath          = JPATH_ROOT . '/www' . $this->app->get('uri.media.path');

		// First check if the locale as given has a file that exists
		$localeSegment = strtr($localePathSegment, ['%locale%' => $locale]);

		if (file_exists($basePath . $localeSegment))
		{
			return $this->getAssetUrl($localeSegment, 'debug');
		}

		// Doesn't exist, check the first segment of the locale now if it's got a - in it (i.e. fr-FR)
		if (strpos($locale, '-') !== false)
		{
			$langSegments = explode('-', $locale);

			$localeSegment = strtr($localePathSegment, ['%locale%' => $langSegments[0]]);

			if (file_exists($basePath . $localeSegment))
			{
				return $this->getAssetUrl($localeSegment, 'debug');
			}
		}

		// We don't have a locale, load English
		$localeSegment = strtr($localePathSegment, ['%locale%' => 'en-GB']);

		return $this->getAssetUrl($localeSegment, 'debug');
	}

	/**
	 * Returns the locale code for the Bootstrap Datepicker
	 *
	 * @param   string  $locale  The locale to load
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getDatepickerLocaleCode($locale)
	{
		$localePathSegment = 'js/vendor/bootstrap-datepicker/locales/bootstrap-datepicker.%locale%.min.js';
		$basePath          = JPATH_ROOT . '/www' . $this->app->get('uri.media.path');

		// First check if the locale as given has a file that exists
		$localeSegment = strtr($localePathSegment, ['%locale%' => $locale]);

		if (file_exists($basePath . $localeSegment))
		{
			return $locale;
		}

		// Doesn't exist, check the first segment of the locale now if it's got a - in it (i.e. fr-FR)
		if (strpos($locale, '-') !== false)
		{
			$langSegments = explode('-', $locale);

			$localeSegment = strtr($localePathSegment, ['%locale%' => $langSegments[0]]);

			if (file_exists($basePath . $localeSegment))
			{
				return $langSegments[0];
			}
		}

		// We don't have a locale, load English
		return 'en-GB';
	}
}
