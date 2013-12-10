<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *
 * $Id: Tx_Formhandler_PreProcessor_ClearTempFiles.php 52405 2011-09-23 08:57:48Z reinhardfuehricht $
 *                                                                        */

/**
 * A pre processor cleaning old files in the temporary upload folder if set.
 * 
 * Example:
 * <code>
 * preProcessors.1.class = Tx_Formhandler_PreProcessor_ClearTempFiles
 *
 * preProcessors.1.config.clearTempFilesOlderThan.value = 17
 * preProcessors.1.config.clearTempFilesOlderThan.unit = hours
 * </code>
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	Tx_Formhandler
 * @subpackage	PreProcessor
 */
class Tx_Formhandler_PreProcessor_ClearTempFiles extends Tx_Formhandler_AbstractPreProcessor {

	/**
	 * The main method called by the controller
	 *
	 * @param array $gp The GET/POST parameters
	 * @param array $settings The defined TypoScript settings for the finisher
	 * @return array The probably modified GET/POST parameters
	 */
	public function process() {
		$this->olderThanValue = $this->settings['clearTempFilesOlderThan.']['value'];
		$this->olderThanUnit = $this->settings['clearTempFilesOlderThan.']['unit'];
		if (!empty($this->olderThanValue) && is_numeric($this->olderThanValue)) {
			$this->clearTempFiles($this->olderThanValue, $this->olderThanUnit);
		}
		return $this->gp;
	}

	/**
	 * Deletes all files older than a specific time in a temporary upload folder.
	 * Settings for the threshold time and the folder are made in TypoScript.
	 *
	 * @param integer $olderThanValue Delete files older than this value.
	 * @param string $olderThanUnit The unit for $olderThan. May be seconds|minutes|hours|days
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	protected function clearTempFiles($olderThanValue, $olderThanUnit) {
		if (!$olderThanValue) {
			return;
		}

		$uploadFolder = $this->utilityFuncs->getTempUploadFolder();

		//build absolute path to upload folder
		$path = $this->utilityFuncs->getDocumentRoot() . $uploadFolder;

		//read files in directory
		$tmpFiles = t3lib_div::getFilesInDir($path);

		$this->utilityFuncs->debugMessage('cleaning_temp_files', array($path));

		//calculate threshold timestamp
		//hours * 60 * 60 = millseconds
		$threshold = $this->utilityFuncs->getTimestamp($olderThanValue, $olderThanUnit);

		//for all files in temp upload folder
		foreach ($tmpFiles as $idx => $file) {

			//if creation timestamp is lower than threshold timestamp
			//delete the file
			$creationTime = filemtime($path . $file);

			//fix for different timezones
			$creationTime += date('O') / 100 * 60;

			if ($creationTime < $threshold) {
				unlink($path . $file);
				$this->utilityFuncs->debugMessage('deleting_file', array($file));
			}
		}
	}

}
?>
