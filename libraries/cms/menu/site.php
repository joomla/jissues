<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Menu
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * JMenu class
 *
 * @package     Joomla.Libraries
 * @subpackage  Menu
 * @since       1.5
 */
class JMenuSite extends JMenu
{
	/**
	 * Loads the entire menu table into memory.
	 *
	 * @return  array
	 */
	public function load()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('m.id, m.menutype, m.title, m.alias, m.note, m.path AS route, m.link, m.type, m.level, m.language');
		$query->select($db->quoteName('m.browserNav') . ', m.access, m.params, m.home, m.img, m.template_style_id, m.component_id, m.parent_id');
		$query->select('e.element as component');
		$query->from('#__menu AS m');
		$query->leftJoin('#__extensions AS e ON m.component_id = e.extension_id');
		$query->where('m.published = 1');
		$query->where('m.parent_id > 0');
		$query->where('m.client_id = 0');
		$query->order('m.lft');

		// Set the query
		$db->setQuery($query);

		try
		{
			$this->_items = $db->loadObjectList('id');
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, JText::sprintf('JERROR_LOADING_MENUS', $e->getMessage()));
			return false;
		}

		foreach ($this->_items as &$item)
		{
			// Get parent information.
			$parent_tree = array();
			if (isset($this->_items[$item->parent_id]))
			{
				$parent_tree  = $this->_items[$item->parent_id]->tree;
			}

			// Create tree.
			$parent_tree[] = $item->id;
			$item->tree = $parent_tree;

			// Create the query array.
			$url = str_replace('index.php?', '', $item->link);
			$url = str_replace('&amp;', '&', $url);

			parse_str($url, $item->query);
		}
	}

	/**
	 * Gets menu items by attribute
	 *
	 * @param   string   $attributes  The field name
	 * @param   string   $values      The value of the field
	 * @param   boolean  $firstonly   If true, only returns the first item found
	 *
	 * @return	array
	 */
	public function getItems($attributes, $values, $firstonly = false)
	{
		$attributes = (array) $attributes;
		$values 	= (array) $values;
		$app		= JFactory::getApplication('site');

		if ($app->isSite())
		{
			// Filter by language if not set
			if (($key = array_search('language', $attributes)) === false)
			{
				if ($app->getLanguageFilter())
				{
					$attributes[] 	= 'language';
					$values[] 		= array(JFactory::getLanguage()->getTag(), '*');
				}
			}
			elseif ($values[$key] === null)
			{
				unset($attributes[$key]);
				unset($values[$key]);
			}

			// Filter by access level if not set
			if (($key = array_search('access', $attributes)) === false)
			{
				$attributes[] = 'access';
				$values[] = JFactory::getUser()->getAuthorisedViewLevels();
			}
			elseif ($values[$key] === null)
			{
				unset($attributes[$key]);
				unset($values[$key]);
			}
		}

		return parent::getItems($attributes, $values, $firstonly);
	}

	/**
	 * Get menu item by id
	 *
	 * @param   string  $language  The language code.
	 *
	 * @return  object  The item object
	 *
	 * @since   1.5
	 */
	public function getDefault($language = '*')
	{
		if (array_key_exists($language, $this->_default) && JApplication::getInstance('site')->getLanguageFilter())
		{
			return $this->_items[$this->_default[$language]];
		}
		elseif (array_key_exists('*', $this->_default))
		{
			return $this->_items[$this->_default['*']];
		}
		else
		{
			return 0;
		}
	}

}
