<?php
/**
 * Part of the Joomla Tracker Pagination Package
 *
 * Taken from:
 * http://www.awcore.com/dev/1/3/Create-Awesome-PHPMYSQL-Pagination_en
 * and modified by "The Joomla! Tracker Project".
 *
 * @copyright  1234 abc
 * @license    1234 abc
 */

namespace JTracker\Pagination;

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
	 * @var    integer
	 * @since  1.0
	 */
	protected $total = 0;

	/**
	 * Current page number.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $page = 0;

	/**
	 * Items per page.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $perPage = 0;

	/**
	 * The current URI.
	 *
	 * @var    Uri
	 * @since  1.0
	 */
	protected $uri;

	/**
	 * Constructor.
	 *
	 * @param   integer  $total    Total items count.
	 * @param   integer  $current  The current item number.
	 * @param   integer  $perPage  Items per page.
	 *
	 * @since   1.0
	 */
	public function __construct(Uri $uri)
	{
//		$this->total   = $total;
//		$this->perPage = $perPage;
//		$this->page    = $current ? floor($current / $perPage) + 1 : 1;
		$this->uri     = $uri;

		// @$this->uri     = new Uri($app->get('uri.request'));
	}

	public function setValues($total, $current, $perPage)
	{
		$this->total   = $total;
		$this->perPage = $perPage;
		$this->page    = $current ? floor($current / $perPage) + 1 : 1;

		return $this;
	}

	/**
	 * Get the current page number.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function getPageNo()
	{
		return ($this->page == 0) ? 1 : $this->page;
	}

	/**
	 * Get the total pages count.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function getPagesTotal()
	{
		return ceil($this->total / ($this->perPage ? : 1));
	}

	/**
	 * Get the rendered pagination.
	 *
	 * @return  string
	 *
	 * @since   1.0
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
			$bar[] = '<li><a href="' . $this->uri($next) . '">' . g11n3t('Next') . '</a></li>';
			$bar[] = '<li><a href="' . $this->uri($lastPage) . '">' . g11n3t('Last') . '</a></li>';
		}
		else
		{
			$bar[] = '<li><a class="current">' . g11n3t('Next') . '</a></li>';
			$bar[] = '<li><a class="current">' . g11n3t('Last') . '</a></li>';
		}

		$bar[] = '</ul>';

		return implode("\n", $bar);
	}

	/**
	 * Get the Uri object for a given page.
	 *
	 * @param   integer  $page  The page number.
	 *
	 * @return  Uri
	 *
	 * @since   1.0
	 */
	private function uri($page)
	{
		$this->uri->setVar('page', $page);

		return $this->uri;
	}
}
