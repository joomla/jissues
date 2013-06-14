<?php
/**
 * Taken from:
 * http://www.awcore.com/dev/1/3/Create-Awesome-PHPMYSQL-Pagination_en
 * and modified by "The Joomla! Tracker Project".
 *
 * @copyright  1234 abc
 * @license    1234 abc
 */

namespace JTracker\Pagination;

use Joomla\Factory;
use Joomla\Uri\Uri;

/**
 * Class TrackerPagination.
 *
 * Taken from:
 * http://www.awcore.com/dev/1/3/Create-Awesome-PHPMYSQL-Pagination_en
 * and modified by "The Joomla! Tracker Project".
 *
 * @since  1.0
 */
class TrackerPagination
{
	/**
	 * Total pages.
	 *
	 * @since  1.0
	 * @var integer
	 */
	protected $total;

	/**
	 * Current page number.
	 *
	 * @since  1.0
	 * @var integer
	 */
	protected $page;

	/**
	 * Items per page.
	 *
	 * @since  1.0
	 * @var integer
	 */
	protected $perPage;

	/**
	 * The current URI.
	 *
	 * @since  1.0
	 * @var Uri
	 */
	protected $uri;

	/**
	 * Constructor.
	 *
	 * @param   integer  $total    Total items count.
	 * @param   integer  $current  The current item number.
	 * @param   integer  $perPage  Items per page.
	 */
	public function __construct($total, $current, $perPage)
	{
		$this->total   = $total;
		$this->perPage = $perPage;
		$this->page    = $current ? floor($current / $perPage) + 1 : 1;
		$this->uri     = new Uri(Factory::$application->get('uri.request'));
	}

	/**
	 * Get the current page number.
	 *
	 * @since  1.0
	 * @return integer
	 */
	public function getPageNo()
	{
		return ($this->page == 0) ? 1 : $this->page;
	}

	/**
	 * Get the total pages count.
	 *
	 * @since  1.0
	 * @return integer
	 */
	public function getPagesTotal()
	{
		return ceil($this->total / $this->perPage);
	}

	/**
	 * Get the rendered pagination.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getBar()
	{
		$neighbours = 2;

		$page = $this->getPageNo();

		$next     = $page + 1;
		$lastPage = $this->getPagesTotal();
		$lpm1     = $lastPage - 1;

		$bar = array();
		$counter    = 0;

		if ($lastPage < 2)
		{
			return $bar;
		}

		$bar[] = '<ul class="trackerPagination">';

		if ($lastPage < 7 + ($neighbours * 2))
		{
			for ($counter = 1; $counter <= $lastPage; $counter++)
			{
				if ($counter == $page)
				{
					$bar[] = '<li><a class="current">' . $counter . '</a></li>';
				}
				else
				{
					$bar[] = '<li><a href="' . $this->uri($counter) . '">' . $counter . '</a></li>';
				}
			}
		}
		elseif ($lastPage > 5 + ($neighbours * 2))
		{
			if ($page < 1 + ($neighbours * 2))
			{
				for ($counter = 1; $counter < 4 + ($neighbours * 2); $counter++)
				{
					if ($counter == $page)
					{
						$bar[] = '<li><a class="current">' . $counter . '</a></li>';
					}
					else
					{
						$bar[] = '<li><a href="' . $this->uri($counter) . '">' . $counter . '</a></li>';
					}
				}

				$bar[] = '<li class="dot">...</li>';
				$bar[] = '<li><a href="' . $this->uri($lpm1) . '">' . $lpm1 . '</a></li>';
				$bar[] = '<li><a href="' . $this->uri($lastPage) . '">' . $lastPage . '</a></li>';
			}
			elseif ($lastPage - ($neighbours * 2) > $page && $page > ($neighbours * 2))
			{
				$bar[] = '<li><a href="' . $this->uri(1) . '">1</a></li>';
				$bar[] = '<li><a href="' . $this->uri(2) . '">2</a></li>';
				$bar[] = '<li class="dot">...</li>';

				for ($counter = $page - $neighbours; $counter <= $page + $neighbours; $counter++)
				{
					if ($counter == $page)
					{
						$bar[] = '<li><a class="current">' . $counter . '</a></li>';
					}
					else
					{
						$bar[] = '<li><a href="' . $this->uri($counter) . '">' . $counter . '</a></li>';
					}
				}

				$bar[] = '<li class="dot">..</li>';
				$bar[] = '<li><a href="' . $this->uri($lpm1) . '">' . $lpm1 . '</a></li>';
				$bar[] = '<li><a href="' . $this->uri($lastPage) . '">' . $lastPage . '</a></li>';
			}
			else
			{
				$bar[] = '<li><a href="' . $this->uri(1) . '">1</a></li>';
				$bar[] = '<li><a href="' . $this->uri(2) . '">2</a></li>';
				$bar[] = '<li class="dot">..</li>';

				for ($counter = $lastPage - (2 + ($neighbours * 2)); $counter <= $lastPage; $counter++)
				{
					if ($counter == $page)
					{
						$bar[] = '<li><a class="current">' . $counter . '</a></li>';
					}
					else
					{
						$bar[] = '<li><a href="' . $this->uri($counter) . '">' . $counter . '</a></li>';
					}
				}
			}
		}

		if ($page < $counter - 1)
		{
			$bar[] = '<li><a href="' . $this->uri($next) . '">Next</a></li>';
			$bar[] = '<li><a href="' . $this->uri($lastPage) . '">Last</a></li>';
		}
		else
		{
			$bar[] = '<li><a class="current">Next</a></li>';
			$bar[] = '<li><a class="current">Last</a></li>';
		}

		$bar[] = '</ul>';

		return implode("\n", $bar);
	}

	/**
	 * Get the uri with a given page.
	 *
	 * @param   integer  $page  The page number.
	 *
	 * @since  1.0
	 * @return Uri
	 */
	private function uri($page)
	{
		$this->uri->setVar('page', $page);

		return $this->uri;
	}
}
