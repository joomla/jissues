<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use JTracker\Helper\LanguageHelper;

/**
 * Class for compiling multiple images into a big one (CSS spriting).
 *
 * @since  1.0
 */
class Languageflags extends Make
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->addOption(
			new TrackerCommandOption(
				'imagefile', '',
				g11n3t('Full path to the combined image file.')
			)
		)
			->addOption(
			new TrackerCommandOption(
				'cssfile', '',
				g11n3t('Full path to the CSS file.')
			)
		);

		$this->description = g11n3t('Compile multiple images into a big one (CSS spriting).');
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle(g11n3t('Compile Language flag images'));

		// @test
		$basePath = JPATH_ROOT . '/www/fff/flagimages';

		$resultImageFile = $this->getApplication()->input->getCmd('imagefile', JPATH_ROOT . '/cache/flags.png');
		$resultCssFile = $this->getApplication()->input->getCmd('cssfile', JPATH_ROOT . '/cache/flags.css');

		$flagWidth = 16;
		$flagHeight = 10;
		$imagesPerRow = 15;
		$flags = ['-verbose'];

		$fileNames = [];

		$cssLines = ['.flag {',
			'	width: ' . $flagWidth . 'px;',
			'	height: ' . $flagHeight . 'px;',
			'	background:url(flags.png) no-repeat',
			'}',
			''
		];

		$colCount = 0;
		$rowCount = 0;

		foreach (LanguageHelper::getLanguageCodes() as $code)
		{
			$fileNames[] = $basePath . '/' . LanguageHelper::getLanguageTagByCode($code) . '.png';

			$xPos = $colCount ? '-' . $colCount * $flagWidth . 'px' : '0';
			$yPos = $rowCount ? '-' . $rowCount * $flagHeight . 'px' : '0';

			$cssLines[] = sprintf('.flag.flag-%s {background-position: %s %s}', $code, $xPos, $yPos);

			$colCount++;

			if ($colCount >= $imagesPerRow)
			{
				$colCount = 0;
				$rowCount++;
			}
		}

		// See: https://www.imagemagick.org/Usage/montage/
		$command = sprintf(
			'montage %s -tile %sx -geometry +0+0 %s %s',
			implode(' ', $fileNames),
			$imagesPerRow,
			implode(' ', $flags),
			$resultImageFile
		);

		$this->out(sprintf(g11n3t('Generating the combined image for %1$d flag images in %2$s'), count($fileNames), $resultImageFile))
			->debugOut($command);

		$this->execCommand($command);

		$this->out()
			->out(sprintf(g11n3t('Writing the CSS file to %s'), $resultCssFile));

		file_put_contents($resultCssFile, implode("\n", $cssLines));

		$this->out()
			->out('Finished.');
	}
}
