<?php
/**
 * Part of the Joomla Tracker Package
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Crowdin\Api;

use Akeneo\Crowdin\Api\AbstractApi;

/**
 * This method exports single translated files from Crowdin.
 * Additionally, it can be applied to export XLIFF files for offline localization. (@todo)
 *
 * @see    https://crowdin.net/page/api/export-file
 *
 * @since  1.0
 */
class ExportFile extends AbstractApi
{
	/** @var string */
	protected $file;

	/** @var string */
	protected $language;

	/** @var string */
	protected $sink;

	/**
	 * Call the api method with provided parameters.
	 *
	 * @return mixed
	 */
	public function execute()
	{
		$path = sprintf(
			'project/%s/export-file?key=%s&file=%s&language=%s',
			$this->client->getProjectIdentifier(),
			$this->client->getProjectApiKey(),
			$this->getFile(),
			$this->getLanguage()
		);

		$response = $this->client->getHttpClient()->get(
			$path, ['sink' => $this->getSink()]
		);

		return $response->getBody();
	}

	/**
	 * Set the file path.
	 *
	 * @param   string  $file  The file path.
	 *
	 * @return ExportFile
	 */
	public function setFile($file)
	{
		$this->file = $file;

		return $this;
	}

	/**
	 * Set the language.
	 *
	 * @param   string  $language  The language string.
	 *
	 * @return ExportFile
	 */
	public function setLanguage($language)
	{
		$this->language = $language;

		return $this;
	}

	/**
	 * Get the file name.
	 *
	 * @return string
	 */
	public function getFile()
	{
		if (!$this->file)
		{
			throw new \UnexpectedValueException('File not set');
		}

		return $this->file;
	}

	/**
	 * Get the language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		if (!$this->language)
		{
			throw new \UnexpectedValueException('Language not set');
		}

		return $this->language;
	}

	/**
	 * Set the sink.
	 *
	 * @param   string  $sink  The sink path.
	 *
	 * @return ExportFile
	 */
	public function setSink($sink)
	{
		$this->sink = $sink;

		return $this;
	}

	/**
	 * Get the sink.
	 *
	 * @return mixed
	 */
	public function getSink()
	{
		if (!$this->sink)
		{
			throw new \UnexpectedValueException('Sink not set');
		}

		return $this->sink;
	}
}
