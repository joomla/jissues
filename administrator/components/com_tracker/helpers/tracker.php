<?php
/**
 * User: elkuku
 * Date: 10.10.12
 * Time: 18:01
 */

abstract class TrackerHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @throws RuntimeException
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function addSubmenu($vName)
	{
		$input = JFactory::getApplication()->input;

		$fields = new JRegistry($input->get('fields', array(), 'array'));

		$project   = (int) $fields->get('project');
		$extension = $input->getString('extension');

		if ('com_categories' == $input->get('option'))
		{
			// Set the Toolbar title in com_categories
			self::setCategoriesTitle($extension);
		}

		JHtmlSidebar::addEntry(
			JText::_('JTracker'),
			'index.php?option=com_tracker',
			$vName == 'tracker' || $vName == '' && !(boolean) $project
		);

		$baseLink = 'index.php?option=com_categories&extension=com_tracker';

		JHtmlSidebar::addEntry(JText::_('Projects'), $baseLink, $extension == 'com_tracker');
		JHtmlSidebar::addEntry(JText::_('Categories'), $baseLink . '.categories', preg_match('/com_tracker.categories[.0-9]*/', $extension));
		JHtmlSidebar::addEntry(JText::_('Textfields'), $baseLink . '.textfields', $extension == 'com_tracker.textfields');
		JHtmlSidebar::addEntry(JText::_('Selectlists'), $baseLink . '.fields', $extension == 'com_tracker.fields');
		JHtmlSidebar::addEntry(JText::_('Checkboxes'), $baseLink . '.checkboxes', $extension == 'com_tracker.checkboxes');

		/*
		 * Select fields
		 */

		preg_match('/com_tracker.fields.([0-9]+)/', $extension, $matches);

		if (isset($matches[1]))
		{
			JHtmlSidebar::addEntry(
				sprintf(JText::_('Selectlists %s'), $matches[1])
				, 'index.php?option=com_categories&extension=com_tracker.fields.' . $matches[1]
				, true
			);
		}

		/*
		 * Global fields
		 */
		if ($project || ($extension && $extension !== 'com_tracker'))
		{
			$p = $project;

			if (!$p)
			{
				preg_match('/com_tracker.([0-9]+)./', $extension, $matches);

				if (isset($matches[1]))
				{
					$p = $matches[1];
				}
			}

			if (!$p)
			{
				return;
			}

			JHtmlSidebar::addEntry(
				sprintf(JText::_('Project %s'), $p)
				, 'index.php?option=com_tracker&project=' . $p
				, (boolean) $project
			);

			JHtmlSidebar::addEntry(
				sprintf(JText::_('%s Categories'), $p)
				, sprintf($baseLink . '.%s.%s', $p, 'categories')
				, preg_match('/com_tracker.[0-9]+.categories/', $extension)
			);

			JHtmlSidebar::addEntry(
				sprintf(JText::_('%s Textfields'), $p)
				, sprintf($baseLink . '.%s.%s', $p, 'textfields')
				, preg_match('/com_tracker.[0-9]+.textfields/', $extension)
			);

			JHtmlSidebar::addEntry(
				sprintf(JText::_('%s Selectlists'), $p)
				, sprintf($baseLink . '.%s.%s', $p, 'fields')
				, preg_match('/com_tracker.[0-9]+.fields/', $extension)
			);

			JHtmlSidebar::addEntry(
				sprintf(JText::_('%s Checkboxes'), $p)
				, sprintf($baseLink . '.%s.%s', $p, 'checkboxes')
				, preg_match('/com_tracker.[0-9]+.checkboxes/', $extension)
			);
		}
	}

	private static function setCategoriesTitle($extension)
	{
		$parts = explode('.', $extension);

		$section    = '';
		$subSection = '';

		if (2 == count($parts))
		{
			$section = $parts[1];
		}

		if (3 == count($parts))
		{
			$section    = $parts[2];
			$subSection = $parts[1];
		}

		JToolbarHelper::title(sprintf('Tracker %1$s %2$s', $section, $subSection));
	}
}
