<?php
/**
 * @package    Kisskontent
 *
 * @copyright  Copyright (C) 2012-2012 Nikolai Plath - elkuku.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') || die('=;)');

// Import Joomla! Plugin library file @todo remove
jimport('joomla.plugin.plugin');

/**
 * Content plugin for the KISSKontent component.
 *
 * @package  KISSKontent
 * @since    1.0
 */
class PlgContentKISSKontent extends JPlugin
{
	/**
	 * Parse text in different formats.
	 *
	 * @param   string     $context  The "context".
	 * @param   object     $row      The data object.
	 * @param   JRegistry  $params   Parameters
	 * @param   integer    $page     ¿
	 *
	 * @return bool
	 */
	public function onContentPrepare($context, $row, $params, $page = 0)
	{
		if ('' == $row->text)
		{
			return true;
		}

		if ('?' == $context)
		{
			// @todo be somewhat "context" specific ?
		}

		$this->setup($params);

		$row->text = Markdown($row->text);

		return true;

		// ##### @TODO include other parser..

		include_once JPATH_SITE . '/plugins/content/kisskontent/parser/classTextile.php';

		//         include_once JPATH_SITE.'/plugins/content/jTextile/textile/smartypants.php';

		$textile = new Textile;

		//         $row->text = '<!-- jTextile -->'."\n".SmartyPants($textile->TextileThis($row->text));
		$row->text = '<!-- jTextile -->' . "\n" . $textile->TextileThis($row->text);

		return true;
	}

	/**
	 * Setup the environment.
	 *
	 * @param   JRegistry  $params  Parameters.
	 *
	 * @return PlgContentKISSKontent
	 */
	private function setup($params)
	{
		static $setupComplete = false;

		if ($setupComplete)
		{
			return $this;
		}

		// Prepare code highlighting
		// JHtmlLuminous::load();
		jimport('luminous.loader');

		$theme = $params->get('luminous.theme', 'github');

		JFactory::getDocument()->addStyleSheet('media/luminous/css/' . $theme . '.css');

		luminous::set('theme', $theme);
		luminous::set('line_numbers', $params->get('luminous.line_numbers', false));
		luminous::set('format', $params->get('luminous.format', 'html'));

		// TEST include_once __DIR__ . '/parser/markdown.php';
		include_once __DIR__ . '/parser/emarkdown.php';

		$setupComplete = true;

		return $this;
	}

}
