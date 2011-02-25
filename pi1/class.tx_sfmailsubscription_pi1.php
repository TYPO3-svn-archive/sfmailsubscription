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
	var $error = false;

	var $requiredFieldsForTable = array(
		'fe_users' => 'username,password,usergroup',
		'tt_address' => ''
	);
	
	
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
		
		$content = $this->generateFields();
		if(!$this->error) {
			if($this->piVars['action'] == 'create') {
				$status = $this->createUser();
				if($status === true) {
					$content = 'Benutzer wurde angelegt';
				} else {
					$content = $status;
				}
			}
			if($this->piVars['action'] == 'update') {
				$this->updateUser();
			}
		}
	
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * initializes the plugin
	 */
	protected function init() {
		//Initialize Flexform
		$this->pi_initPIflexForm();

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		// get language object to translate field labels
		$this->lang = t3lib_div::makeInstance('language');
		$this->lang->init($this->language);

		// set Swiftmail
		$this->mailer = t3lib_div::makeInstance('t3lib_mail_message');
		$this->mailer->setFrom(array($this->extConf['from_email'] => $this->extConf['from_name']));
		
		// Set language
		if($GLOBALS['TSFE']->tmpl->setup['config.']['language']) {
			$this->language = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
		}
		
		// Get Flexformvalues
		$this->conf['pid'] = $this->fetchConfigurationValue('pid');
		$this->conf['table'] = $this->fetchConfigurationValue('table');
		$this->conf['userGroup'] = $this->fetchConfigurationValue('userGroup');
		$this->conf['fieldList'] = $this->fetchConfigurationValue('fieldList');
		$this->conf['fieldListRequired'] = $this->fetchConfigurationValue('fieldListRequired');

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
	 * Generate Fields
	 */
	protected function generateFields() {
		t3lib_div::loadTCA($this->conf['table']);

		// get subpart of all fields
		$subpartArray['###FIELDS###'] = $this->cObj->getSubpart($this->template['total'], '###FIELDS###');

		// replace all label and name markers
		foreach(t3lib_div::trimExplode(',', $this->conf['fieldList']) as $value) {
			$markerArray['###FIELDNAME_' . strtoupper($value) . '###'] = $this->prefixId . '[' . $value . ']';
			$markerArray['###LABEL_' . strtoupper($value) . '###'] = $this->lang->sL($GLOBALS['TCA'][$this->conf['table']]['columns'][$value]['label']);;
			$markerArray['###MANDATORY_' . strtoupper($value) . '###'] = '';

			// show errors only if there is something to create or change
			if($this->piVars['action'] == 'create' || $this->piVars['action'] == 'update') {
				$markerArray['###ERROR_' . strtoupper($value) . '###'] = $this->checkField($value, $GLOBALS['TCA'][$this->conf['table']]['columns'][$value]['label']);
			} else {
				$markerArray['###ERROR_' . strtoupper($value) . '###'] = '';
			}
		}

		// overwrite empty mandatory marker with mandatory star
		foreach(t3lib_div::trimExplode(',', $this->conf['fieldListRequired']) as $value) {
			$markerArray['###MANDATORY_' . strtoupper($value) . '###'] = $this->cObj->wrap('*', $this->conf['wrapMandatory']);
		}

		// replace plugin markers
		$markerArray['###ACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id, '_TOP', array('no_cache' => 1));
		$markerArray['###FIELDNAME_SUBMIT###'] = $this->prefixId . '[submit]';
		$markerArray['###LABEL_SUBMIT###'] = $this->pi_getLL('label_submit');
		if(count($this->piVars) == 0) {
			$markerArray['###HIDDEN_FIELDS###'] = '<input type="hidden" name="' . $this->prefixId . '[action]" value="create" />';
		} else {
			$markerArray['###HIDDEN_FIELDS###'] = '';
		}

		return $this->cObj->substituteMarkerArray($subpartArray['###FIELDS###'], $markerArray);
	}

	/**
	 * Check field for validation
	 */
	protected function checkField($field, $lang) {
		// checks if field is required
		$fieldListArray = t3lib_div::trimExplode(',', $this->conf['fieldListRequired']);
		foreach($fieldListArray as $value) {
			// if field was found in required list
			if($value == $field) {
				// check if not empty
				if($this->piVars[$field] != '') {
					return '';
				} else {
					$this->error = true;
					$fieldLabel = $this->cObj->wrap($this->lang->sL($lang), $this->conf['wrapErrorRequiredField']);
					$errorRequired = $this->cObj->wrap(
						sprintf(
							$this->pi_getLL('error_required'),
							$fieldLabel
						),
						$this->conf['wrapErrorRequired']
					);
					return $errorRequired;
				}
			}
		}
	}

	function createUser() {
		// set some default if table = fe_users
		$this->piVars['username'] = $this->piVars['email'];
		$this->piVars['password'] = substr(md5($this->piVars['email']), 0, 8);
		$this->piVars['usergroup'] = $this->conf['userGroup'];
		$this->piVars['module_sys_dmail_newsletter'] = 1;
		$fieldListForTable = $this->conf['fieldList'] . ',' . $this->requiredFieldsForTable[$this->conf['table']];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			$this->conf['table'],
			'email = "' . $this->piVars['email'] . '"' .
			$this->cObj->enableFields($this->conf['table']),
			'', '', ''
		);

		// if record was found decide if an error occours or the record should be updated
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$user = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			if($this->conf['updateUserIfPossible']) {
				$this->cObj->DBgetUpdate($this->conf['table'], $user['uid'], $this->piVars, $fieldListForTable, TRUE);
			} else {
				return $this->pi_getLL('error_allreadyInDB');
			}
		} else {
			$this->cObj->DBgetInsert($this->conf['table'], $this->conf['pid'], $this->piVars, $fieldListForTable, TRUE);
		}
		return true;
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