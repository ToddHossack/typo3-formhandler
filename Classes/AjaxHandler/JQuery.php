<?php
namespace Typoheads\Formhandler\AjaxHandler;
    /*                                                                       *
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
    *                                                                        */

/**
 * Abstract class for an AjaxHandler.
 * The AjaxHandler takes care of adding AJAX related markers and JS used for validation and file removal.
 *
 * @author    Reinhard Führicht <rf@typoheads.at>
 * @abstract
 */
class JQuery extends \Typoheads\Formhandler\AjaxHandler\AbstractAjaxHandler
{
    /**
     * Position of JS generated by AjaxHandler_JQuery (head|footer)
     *
     * @access protected
     * @var string
     */
    protected $jsPosition;

    /**
     * Initialize AJAX stuff
     *
     * @return void
     */
    public function initAjax()
    {
        $this->validationStatusClasses = [
            'base' => 'formhandler-validation-status',
            'valid' => 'form-valid',
            'invalid' => 'form-invalid'
        ];
        if (is_array($this->settings['validationStatusClasses.'])) {
            if ($this->settings['validationStatusClasses.']['base']) {
                $this->validationStatusClasses['base'] = $this->utilityFuncs->getSingle($this->settings['validationStatusClasses.'], 'base');
            }
            if ($this->settings['validationStatusClasses.']['valid']) {
                $this->validationStatusClasses['valid'] = $this->utilityFuncs->getSingle($this->settings['validationStatusClasses.'], 'valid');
            }
            if ($this->settings['validationStatusClasses.']['invalid']) {
                $this->validationStatusClasses['invalid'] = $this->utilityFuncs->getSingle($this->settings['validationStatusClasses.'], 'invalid');
            }
        }

        $autoDisableSubmitButton = $this->utilityFuncs->getSingle($this->settings, 'autoDisableSubmitButton');

        $this->jsPosition = trim($this->utilityFuncs->getSingle($this->settings, 'jsPosition'));
        $isAjaxSubmit = intval($this->utilityFuncs->getSingle($this->settings, 'ajaxSubmit'));

        $submitButtonSelector = $this->utilityFuncs->getSingle($this->settings, 'submitButtonSelector');
        if (strlen(trim($submitButtonSelector)) === 0) {
            $submitButtonSelector = 'INPUT[type=\'submit\']';
        }
        $submitButtonSelector = str_replace('"', '\"', $submitButtonSelector);

        $globalSettings = $this->globals->getSession()->get('settings');
        $validateFields = [];
        if (is_array($globalSettings['validators.']) && intval($this->utilityFuncs->getSingle($globalSettings['validators.'], 'disable')) !== 1) {
            foreach ($globalSettings['validators.'] as $key => $validatorSettings) {
                if (is_array($validatorSettings['config.']['fieldConf.']) && intval($this->utilityFuncs->getSingle($validatorSettings['config.'], 'disable')) !== 1) {
                    foreach ($validatorSettings['config.']['fieldConf.'] as $fieldName => $fieldSettings) {
                        $replacedFieldName = str_replace('.', '', $fieldName);
                        $fieldName = $replacedFieldName;
                        $validateFields[] = $fieldName;
                    }
                }
            }
        }

        $formSelector = '.Tx-Formhandler:has(INPUT[value=\"' . $this->globals->getRandomID() . '\"])';
        $formID = $this->utilityFuncs->getSingle($globalSettings, 'formID');
        if($formID) {
            $formSelector = '.Tx-Formhandler:has(FORM[id=\"' . $formID . '\"])';
        }
        $this->addJS('<script type="text/javascript" src="typo3conf/ext/formhandler/Resources/Public/JavaScript/ajax.js"></script>', 'base', false);
        $this->addJS('<script type="text/javascript">
            (function( $ ) {
                $(function() {
                    $("' . $formSelector . '").formhandler({
                        pageID: "' . $GLOBALS['TSFE']->id . '",
                        contentID: "' . $this->cObj->data['uid'] . '",
                        randomID: "' . $this->globals->getRandomID() . '",
                        formValuesPrefix: "' . $this->globals->getFormValuesPrefix() . '",
                        lang: "' . $GLOBALS['TSFE']->sys_language_uid . '",
                        submitButtonSelector: "' . $submitButtonSelector . '",
                        ajaxSubmit: ' . ($isAjaxSubmit ? "true":"false") . ',
                        autoDisableSubmitButton: ' . ($autoDisableSubmitButton ? "true":"false") . ',
                        validateFields: [\'' . implode("','", $validateFields) . '\'],
                        validationStatusClasses: {
                            base: "' . $this->validationStatusClasses['base'] . '",
                            valid: "' . $this->validationStatusClasses['valid'] . '",
                            invalid: "' . $this->validationStatusClasses['invalid'] . '"
                        }
                    });
                });

            }( jQuery ));
        </script>', 'ext');
    }

    /**
     * Method called by the view to let the AjaxHandler add its markers.
     *
     * The view passes the marker array by reference.
     *
     * @param array &$markers Reference to the marker array
     * @return void
     */
    public function fillAjaxMarkers(&$markers)
    {
        $settings = $this->globals->getSession()->get('settings');
        $ajaxSubmit = $this->utilityFuncs->getSingle($settings['ajax.']['config.'], 'ajaxSubmit');
        if (intval($ajaxSubmit) === 1) {
            $ajaxSubmitLoader = $this->utilityFuncs->getSingle($settings['ajax.']['config.'], 'ajaxSubmitLoader');
            if (strlen($ajaxSubmitLoader) === 0) {
                $loadingImg = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('formhandler') . 'Resources/Public/Images/ajax-loader.gif';
                $loadingImg = '<img src="' . $loadingImg . '" alt="loading" />';
                $loadingImg = str_replace('../', '', $loadingImg);
                $ajaxSubmitLoader = '<span class="loading_ajax-submit">' . $loadingImg . '</span>';
            }
            $markers['###loading_ajax-submit###'] = $ajaxSubmitLoader;
        }

        $autoDisableSubmitButton = $this->utilityFuncs->getSingle($settings['ajax.']['config.'], 'autoDisableSubmitButton');
        if (intval($autoDisableSubmitButton) === 1) {
            $markers['###validation-status###'] = $this->validationStatusClasses['base'] . ' ' . $this->validationStatusClasses['invalid'];
        }

        $initial = $this->utilityFuncs->getSingle($settings['ajax.']['config.'], 'initial');

        $loadingImg = $this->utilityFuncs->getSingle($settings['ajax.']['config.'], 'loading');
        if (strlen($loadingImg) === 0) {
            $loadingImg = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('formhandler') . 'Resources/Public/Images/ajax-loader.gif';
            $loadingImg = str_replace('../', '', $loadingImg);
            $loadingImg = '<img src="' . $loadingImg . '" alt="loading" />';
        }

        if (is_array($settings['validators.']) && intval($this->utilityFuncs->getSingle($settings['validators.'], 'disable')) !== 1) {
            foreach ($settings['validators.'] as $key => $validatorSettings) {
                if (is_array($validatorSettings['config.']['fieldConf.']) && intval($this->utilityFuncs->getSingle($validatorSettings['config.'], 'disable')) !== 1) {
                    foreach ($validatorSettings['config.']['fieldConf.'] as $fieldname => $fieldSettings) {
                        $replacedFieldname = str_replace('.', '', $fieldname);

                        $markers['###validate_' . $replacedFieldname . '###'] = '
							<span class="loading" id="loading_' . $replacedFieldname . '" style="display:none">' . $loadingImg . '</span>
							<span id="result_' . $replacedFieldname . '" class="formhandler-ajax-validation-result">' . str_replace('###fieldname###', $replacedFieldname, $initial) . '</span>
						';
                    }
                }
            }
        }
    }

    /**
     * Method called by the view to get an AJAX based file removal link.
     *
     * @param string $text The link text to be used
     * @param string $field The field name of the form field
     * @param string $uploadedFileName The name of the file to be deleted
     * @return string
     */
    public function getFileRemovalLink($text, $field, $uploadedFileName)
    {
        $params = [
            'eID' => 'formhandler-removefile',
            'field' => $field,
            'uploadedFileName' => $uploadedFileName
        ];
        $url = $this->utilityFuncs->getAjaxUrl($params);
        return '<a class="formhandler_removelink" href="' . $url . '">' . $text . '</a>';
    }

    protected function addJS($js, $key = '', $doAppend = true)
    {
        if ($this->jsPosition === 'inline') {
            $GLOBALS['TSFE']->content .= $js;
        } elseif ($this->jsPosition === 'footer') {
            if($doAppend) {
                $GLOBALS['TSFE']->additionalFooterData['Tx_Formhandler_AjaxHandler_Jquery_' . $key] .= $js;
            } else {
                $GLOBALS['TSFE']->additionalFooterData['Tx_Formhandler_AjaxHandler_Jquery_' . $key] = $js;
            }
        } else {
            if($doAppend) {
                $GLOBALS['TSFE']->additionalHeaderData['Tx_Formhandler_AjaxHandler_Jquery_' . $key] .= $js;
            } else {
                $GLOBALS['TSFE']->additionalHeaderData['Tx_Formhandler_AjaxHandler_Jquery_' . $key] = $js;
            }
        }
    }

}