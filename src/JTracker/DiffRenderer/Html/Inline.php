<?php
/**
 * Part of the Joomla Tracker DiffRenderer Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\DiffRenderer\Html;

use Adaptive\Diff\Renderer\Html\ArrayRenderer;
use Twig\Environment;

/**
 * Class Inline
 *
 * @since  1.0
 */
class Inline extends ArrayRenderer
{
	/**
	 * Array of the default options that apply to this renderer.
	 *
	 * @var    Environment
	 * @since  1.0
	 */
	protected $twig;

	/**
	 * Array of the default options that apply to this renderer.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $defaultOptions = [
		'show_header'       => true,
		'show_line_numbers' => true,
		'tabSize'           => 4,
	];

	/**
	 * Constructor.
	 *
	 * @param   Environment  $twig     The Twig environment to render the template.
	 * @param   array        $options  Optionally, an array of the options for the renderer.
	 *
	 * @since   1.0
	 */
	public function __construct(Environment $twig, array $options = [])
	{
		parent::__construct($options);

		$this->twig = $twig;
	}

	/**
	 * Render and return diff with changes between the two sequences displayed inline (under each other).
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function render()
	{
		$changes = parent::render();

		if (empty($changes))
		{
			return '';
		}

		return $this->twig->render(
			'diff.twig',
			[
				'changes'           => $changes,
				'show_header'       => $this->options['show_header'],
				'show_line_numbers' => $this->options['show_line_numbers'],
			]
		);
	}
}
