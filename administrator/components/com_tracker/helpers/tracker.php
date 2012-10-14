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

		$project = $input->get('project');
		$extension = $input->getString('extension');

		JHtmlSidebar::addEntry(
			JText::_('JTracker'),
			'index.php?option=com_tracker',
			$vName == 'tracker' || $vName == '' && !(boolean) $project
		);

		// Groups and Levels are restricted to core.admin
		//$canDo = self::getActions();

		if (1) //$canDo->get('core.admin'))
		{
			JHtmlSidebar::addEntry(
				JText::_('Projects')
				, 'index.php?option=com_categories&extension=com_tracker'
				, $extension == 'com_tracker'
			);

			JHtmlSidebar::addEntry(
				JText::_('Global categories')
				, 'index.php?option=com_categories&extension=com_tracker.categories'
				, preg_match('/com_tracker.categories[.0-9]*/', $extension)
			);

			JHtmlSidebar::addEntry(
				JText::_('Global fields')
				, 'index.php?option=com_categories&extension=com_tracker.fields'
				, $extension == 'com_tracker.fields'
			);

			preg_match('/com_tracker.fields.([0-9]+)/', $extension, $matches);

			if(isset($matches[1]))
			{
				JHtmlSidebar::addEntry(
					sprintf(JText::_('Global fields %s'), $matches[1])
					, 'index.php?option=com_categories&extension=com_tracker.fields.'.$matches[1]
					, true
				);
			}

			if ($project || ($extension && $extension !== 'com_tracker'))
			{
				$p = $project;

				if (!$p)
				{
					preg_match('/com_tracker.([0-9]+)./', $extension, $matches);

					if (isset($matches[1]))
						$p = $matches[1];
				}

				if ($p)
				{
					$link = 'index.php?option=com_categories&extension=com_tracker';

					JHtmlSidebar::addEntry(
						sprintf(JText::_('Project %s'), $p)
						, 'index.php?option=com_tracker&project=' . $p
						, (boolean) $project
					);

					JHtmlSidebar::addEntry(
						sprintf(JText::_('%s Categories'), $p)
						, sprintf($link . '.%s.%s', $p, 'categories')
						, preg_match('/com_tracker.[0-9]+.categories/', $extension)
					);

					JHtmlSidebar::addEntry(
						sprintf(JText::_('%s Fields'), $p)
						, sprintf($link . '.%s.%s', $p, 'fields')
						, preg_match('/com_tracker.[0-9]+.fields/', $extension)
					);
				}
			}
		}
	}

}
