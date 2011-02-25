<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stefan Froemken <firma@sfroemken.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'SF Mailsubscription' for the 'sfmailsubscription' extension.
 *
 * @author	Stefan Froemken <firma@sfroemken.de>
 * @package	TYPO3
 * @subpackage	tx_sfmailsubscription
 */
class tx_sfmailsubscription_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_sfmailsubscription_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_sfmailsubscription_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'sfmailsubscription';	// The extension key.
	var $pi_checkCHash = true;
	var $extConf = array(); // extension constants
	
	var $language = 'default';  // Set language
	var $templateFile = '/res/pi1_template.html';
	var $template = array();
	var $tempFields = 'first_name,email,telephone,www,address';
	
	
	/**
	 * Languageobject to find the translation within TCA
	 * 
	 * @var language
	 */
	var $lang;
	
	/**
	 * Swiftmail-Object
	 * 
	 * @var t3lib_mail_message
	 */
	var $mailer;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->init();
		
		if($this->piVars['submit']) {
			$content = $this->checkData();
		} else {
			t3lib_div::loadTCA('fe_users');

			$this->lang = t3lib_div::makeInstance('language');
			$this->lang->init($this->language);	
			
			$subpartArray['###FIELDS###'] = $this->cObj->getSubpart($this->template['total'], '###FIELDS###');
			foreach(t3lib_div::trimExplode(',', $this->tempFields) as $value) {
				$markerArray['###FIELDNAME_' . strtoupper($value) . '###'] = $this->prefixId . '[' . $value . ']';
				$markerArray['###LABEL_' . strtoupper($value) . '###'] = $this->lang->sL($GLOBALS['TCA']['fe_users']['columns'][$value]['label']);;
			}
			$markerArray['###ACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id, '_TOP', array('no_cache' => 1));
			$markerArray['###FIELDNAME_SUBMIT###'] = $this->prefixId . '[submit]';
			$markerArray['###LABEL_SUBMIT###'] = $this->pi_getLL('label_submit');

			$content = $this->cObj->substituteMarkerArray($subpartArray['###FIELDS###'], $markerArray);			
		}
	
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * initializes the plugin
	 */
	protected function init() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		// set Swiftmail
		$this->mailer = t3lib_div::makeInstance('t3lib_mail_message');
		$this->mailer->setFrom(array($this->extConf['from_email'] => $this->extConf['from_name']));
		
		// Set language
		if($GLOBALS['TSFE']->tmpl->setup['config.']['language']) {
			$this->language = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
		}
		
		$this->template['total'] = $this->cObj->fileResource(
			'EXT:'.$this->extKey.$this->templateFile
		);

		$this->addHeaderPart();
	}

	/**
	 * generate HeaderData
	 */
	protected function addHeaderPart() {
		$subpart = $this->cObj->getSubpart($this->template['total'], '###CSS###');
		$content = $this->cObj->substituteMarker($subpart, '###PATH###', t3lib_extMgm::siteRelPath($this->extKey).'res/main.css');

		//Ensure that header part is added only once to the page
		//and check if sfjquery should be used only as template engine
		$key = $this->prefixId . '_' . md5($this->template['total']);
		if(!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$GLOBALS['TSFE']->additionalHeaderData[$key] = $content;
		}
	}
	
	/**
	 * Enter description here ...
	 */
	protected function checkData() {
		$this->mailer->setTo(array($this->piVars['email'] => ''));
		$this->mailer->setSubject('Bestätigungsmail'); 
		$this->mailer->setBody('<html><head><title>Titel</title></head><body><p>Ich bin HTML-Text</p></body></html>');

		if($this->mailer->send()) {
			$content = 'Die E-Mail wurde versandt';
		} else {
			$content = 'Fehler beim Versenden der E-Mail';
		}
		
		return $content;
	}

	/**
	 * Fetches configuration value given its name.
	 * Merges flexform and TS configuration values.
	 *
	 * @param		string	$param	Configuration value name
	 * @return	string	Parameter value
	 */
	function fetchConfigurationValue($param, $sheet = 'sDEF') {
		$value = trim($this->pi_getFFvalue(
			$this->cObj->data['pi_flexform'], $param, $sheet)
		);
		return $value ? $value : $this->conf[$param];
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_pi1.php']);
}

?>