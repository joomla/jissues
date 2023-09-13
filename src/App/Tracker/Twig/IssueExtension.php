<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Twig;

use JTracker\Application;
use JTracker\View\Renderer\ContrastHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension integrating miscellaneous issue capabilities
 *
 * @since  1.0
 */
class IssueExtension extends AbstractExtension
{
	/**
	 * A list of the tracker's issue priorities
	 *
	 * @var    array
	 * @since  1.0
	 */
	private const ISSUE_PRIORITIES = [
		1 => 'Critical',
		2 => 'Urgent',
		3 => 'Medium',
		4 => 'Low',
		5 => 'Very low',
	];

	/**
	 * A list of the user test options
	 *
	 * @var    array
	 * @since  1.0
	 */
	private const USER_TEST_OPTIONS = [
		0 => 'Not tested',
		1 => 'Tested successfully',
		2 => 'Tested unsuccessfully',
	];

	/**
	 * Web application
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Cached label data, lazy loaded when needed
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $labels;

	/**
	 * Constructor.
	 *
	 * @param   Application  $app  Web application
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
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
			new TwigFilter('issue_merge_badge', [$this, 'renderIssueMergeBadge'], ['is_safe' => ['html']]),
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
			new TwigFunction('issue_labels', [$this, 'renderIssueLabels'], ['is_safe' => ['html']]),
			new TwigFunction('issue_link', [$this, 'renderIssueLink'], ['is_safe' => ['html']]),
			new TwigFunction('issue_priorities', [$this, 'getIssuePriorities']),
			new TwigFunction('issue_priority', [$this, 'getIssuePriority']),
			new TwigFunction('issue_priority_class', [$this, 'getIssuePriorityClass']),
			new TwigFunction('user_test_options', [$this, 'getUserTestOptions']),
		];
	}

	/**
	 * Generate a translated merge status.
	 *
	 * @param   string  $status  The merge status.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function getIssueMergeStatus($status)
	{
		switch ($status)
		{
			case 'success':
				return 'Success';

			case 'pending':
				return 'Pending';

			case 'error':
				return 'Error';

			case 'failure':
				return 'Failure';

			default:
				throw new \InvalidArgumentException('Unknown status: ' . $status);
		}
	}

	/**
	 * Get a text list of issue priorities.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getIssuePriorities(): array
	{
		return self::ISSUE_PRIORITIES;
	}

	/**
	 * Get the issue priority text.
	 *
	 * @param   integer  $id  The priority id.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getIssuePriority($id): string
	{
		return self::ISSUE_PRIORITIES[$id] ?? 'N/A';
	}

	/**
	 * Get a CSS class according to the issue's priority.
	 *
	 * @param   integer  $priority  The priority
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getIssuePriorityClass($priority): string
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
	 * Get a user test option string.
	 *
	 * @param   integer  $id  The option ID.
	 *
	 * @return  array|string  Option name if a known ID is given otherwise the full options list
	 *
	 * @since   1.0
	 */
	public function getUserTestOptions($id = null)
	{
		return ($id !== null && \array_key_exists($id, self::USER_TEST_OPTIONS)) ? self::USER_TEST_OPTIONS[$id] : self::USER_TEST_OPTIONS;
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
	public function renderIssueLabels($idsString)
	{
		if ($this->labels === null)
		{
			$this->labels = $this->app->getProject()->getLabels();
		}

		$html = [];

		$ids = ($idsString) ? explode(',', $idsString) : [];

		foreach ($ids as $id)
		{
			if (\array_key_exists($id, $this->labels))
			{
				$bgColor = $this->labels[$id]->color;
				$color   = ContrastHelper::getContrastColor($bgColor);
				$name    = $this->labels[$id]->name;
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
	 * Generate HTML output for a merge status badge.
	 *
	 * @param   string  $status  The merge status.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function renderIssueMergeBadge($status)
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
				throw new \InvalidArgumentException('Unknown status: ' . $status);
		}

		return '<span class="badge badge-' . $class . '">' . $this->getIssueMergeStatus($status) . '</span>';
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
	public function renderIssueLink($number, $closed, $title = ''): string
	{
		$title = $title ?: ' #' . $number;
		$href  = $this->app->get('uri.base.path') . 'tracker/' . $this->app->getProject()->alias . '/' . $number;

		$html = '<a href="' . $href . '" title="' . $title . '">';
		$html .= $closed ? '<del># ' . $number . '</del>' : '# ' . $number;
		$html .= '</a>';

		return $html;
	}
}
