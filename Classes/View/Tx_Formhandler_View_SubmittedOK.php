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
 * $Id: Tx_Formhandler_View_SubmittedOK.php 58511 2012-02-25 20:29:05Z reinhardfuehricht $
 *                                                                        */

/**
 * A view for Finisher_SubmittedOK used by Formhandler
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	Tx_Formhandler
 * @subpackage	View
 */
class Tx_Formhandler_View_SubmittedOK extends Tx_Formhandler_View_Form {

	/**
	 * This function fills the default markers:
	 *
	 * ###PRINT_LINK###
	 * ###PDF_LINK###
	 * ###CSV_LINK###
	 *
	 * @return string Template with replaced markers
	 */
	protected function fillDefaultMarkers() {
		parent::fillDefaultMarkers();
		$params = array();
		if ($this->globals->getFormValuesPrefix()) {
			$params[$this->globals->getFormValuesPrefix()] = $this->gp;
		} else {
			$params = $this->gp;
		}
		if ($this->componentSettings['actions.']) {
			foreach ($this->componentSettings['actions.'] as $action=>$options) {
				$sanitizedAction = str_replace('.', '', $action);
				$class = $this->utilityFuncs->getPreparedClassName($options);
				if ($class) {
					$generator = $this->componentManager->getComponent($class);
					$generator->init($this->gp, $options['config.']);
					$markers['###' . strtoupper($sanitizedAction) . '_LINK###'] = $generator->getLink($params);
				}
			}
		}
		$this->fillFEUserMarkers($markers);
		$this->fillFileMarkers($markers);
		$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
	}
	
	public function render($gp, $errors) {

		//set GET/POST parameters
		$this->gp = $gp;

		//set template
		$this->template = $this->subparts['template'];
		if(strlen($this->template) === 0) {
			$this->utilityFuncs->throwException('no_template_file');
		}

		$this->errors = $errors;

		//set language file
		if (!$this->langFiles) {
			$this->langFiles = $this->globals->getLangFiles();
		}

			//fill Typoscript markers
		if (is_array($this->settings['markers.'])) {
			$this->fillTypoScriptMarkers();
		}
		
		//read master template
		if (!$this->masterTemplates) {
			$this->readMasterTemplates();
		}

		if (!empty($this->masterTemplates)) {
			$count = 0;
			while($count < 5 && preg_match('/###(field|master)_[^#]*###/', $this->template)) {
				$this->replaceMarkersFromMaster();
				$count++;
			}
		}
		
		if ($this->globals->getAjaxHandler()) {
			$markers = array();
			$this->globals->getAjaxHandler()->fillAjaxMarkers($markers);
			$this->template = $this->cObj->substituteMarkerArray($this->template, $markers);
		}
		
		//fill Typoscript markers
		if (is_array($this->settings['markers.'])) {
			$this->fillTypoScriptMarkers();
		}

		$this->substituteConditionalSubparts('has_translation');
		if (!$this->gp['submitted']) {
			$this->storeStartEndBlock();
		} elseif (intval($this->globals->getSession()->get('currentStep')) !== 1) {
			$this->fillStartEndBlock();
		}
		
		if (intval($this->settings['fillValueMarkersBeforeLangMarkers']) === 1) {

			//fill value_[fieldname] markers
			$this->fillValueMarkers();
		}

		//fill LLL:[language_key] markers
		$this->fillLangMarkers();

		//substitute ISSET markers
		$this->substituteConditionalSubparts('isset');
		
		//substitute IF markers
		$this->substituteConditionalSubparts('if');

		//fill default markers
		$this->fillDefaultMarkers();

		if (intval($this->settings['fillValueMarkersBeforeLangMarkers']) !== 1) {

			//fill value_[fieldname] markers
			$this->fillValueMarkers();
		}

		//fill selected_[fieldname]_value markers and checked_[fieldname]_value markers
		$this->fillSelectedMarkers();
		
		//fill error_[fieldname] markers
		if (!empty($errors)) {
			$this->fillIsErrorMarkers($errors);
			$this->fillErrorMarkers($errors);
		}

		//fill LLL:[language_key] markers again to make language markers in other markers possible
		$this->fillLangMarkers();

		//remove markers that were not substituted
		$content = $this->utilityFuncs->removeUnfilledMarkers($this->template);
		
		if(is_array($this->settings['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $this->settings['stdWrap.']);
		}
		if(intval($this->settings['disableWrapInBaseClass']) !== 1) {
			$content = $this->pi_wrapInBaseClass($content);
		}

		return $content;
	}
}
?>
