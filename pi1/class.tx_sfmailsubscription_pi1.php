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
//require_once(t3lib_extMgm::siteRelPath($this->extKey).'pi1/tx_sfmailsubscription_mail.php');

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
	var $userArray = array(); // Array with all userdata

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
	 * Mail-Object
	 *
	 * @var tx_sfmailsubscription_mail
	 */
	var $mail;
	
	/**
	 * User-Object
	 *
	 * @var tx_sfmailsubscription_user
	 */
	var $user;
	
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
		
		// some initializings
		$this->init();
		
		// create Template for fields like email, username, ...
		if(t3lib_div::_GET('showEdit')) {
			$content = $this->generateFields('edit');
		} elseif(t3lib_div::_GET('action') == 'update') {
			$content = $this->generateFields('update');
		} else {
			$content = $this->generateFields('create');
		}

		
		// if some required fields are not filled, we can get an error
		if(!$this->error) {
			t3lib_div::devLog('Action', $this->extKey, 0, array($this->piVars['action']));

			// create a new user and send confirmation mail
			if($this->piVars['action'] == 'create') {
				$status = $this->user->createUser();
				if($status === true) {
					$content = $this->pi_getLL('info_sendConfirmationMail');
					$this->mail->sendMail();
				} else {
					$content = $status;
				}
			}
			
			// update an existing user
			if($this->piVars['action'] == 'update') {
				if(($user = $this->user->getUserRecord($this->piVars['uid']))) {
					t3lib_div::devLog('user', $this->extKey, 0, $user);
					$this->userArray = $user;
					$this->user->updateUser($this->userArray['uid']);
				}
			}

			// send edit links
			if($this->piVars['action'] == 'edit') {
				if(($user = $this->user->getUserRecord($this->piVars['email']))) {
					$this->userArray = $user;
					$this->mail->sendMail('sendEditLinks');
				} else {
					$content = 'Ihre E-Mail-Adresse ist mei uns nicht registriert.';
				}
			}

			// If a link in a mail was clicked...
			if(t3lib_div::_GET('authCode') != '') {
				switch(t3lib_div::_GET('action')) {
					case 'create':
						// search in the hidden records
						$this->userArray = $this->user->getUserRecord(intval(t3lib_div::_GET('user')), 1);
						if(t3lib_div::_GET('authCode') == $this->mail->getAuthCode()) {
							if($this->user->activateUser()) {
								$this->mail->sendMail('confirmed');
								$content = $this->pi_getLL('info_emailOK');
							}
						}
						break;
					case 'update':
						/*
						// search in the hidden records
						$this->userArray = $this->user->getUserRecord(intval(t3lib_div::_GET('user')));
						if(t3lib_div::_GET('authCode') == $this->mail->getAuthCode()) {
							if($this->user->updateUser($this->userArray['uid'])) {
								$this->mail->sendMail('updated');
								$content = $this->pi_getLL('info_updateOK');
							}
						}*/
						break;
					case 'delete':
						// search in the hidden records
						$this->userArray = $this->user->getUserRecord(intval(t3lib_div::_GET('user')));
						if(t3lib_div::_GET('authCode') == $this->mail->getAuthCode()) {
							if($this->user->deleteUser($this->userArray['uid'])) {
								$this->mail->sendMail('deleted');
								$content = $this->pi_getLL('info_deleteOK');
							}
						}
						break;
					default:
						$content = 'no action was given.';
						break;
				}
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

		// Get Flexformvalues
		$this->conf['pid'] = $this->fetchConfigurationValue('pid');
		$this->conf['pid'] = ($this->conf['pid']) ? $this->conf['pid'] : 0;
		$this->conf['table'] = $this->fetchConfigurationValue('table');
		$this->conf['table'] = ($this->conf['table']) ? $this->conf['table'] : 'fe_users';
		$this->conf['userGroup'] = $this->fetchConfigurationValue('userGroup');
		$this->conf['fieldList'] = $this->fetchConfigurationValue('fieldList');
		$this->conf['fieldListRequired'] = $this->fetchConfigurationValue('fieldListRequired');
		$this->conf['subject'] = $this->fetchConfigurationValue('subject');
		
		$this->template['total'] = $this->cObj->fileResource(
			'EXT:'.$this->extKey.$this->templateFile
		);

		$this->addHeaderPart();

		// get language object to translate field labels
		$this->lang = t3lib_div::makeInstance('language');
		$this->lang->init($this->language);

		// Set language
		if($GLOBALS['TSFE']->tmpl->setup['config.']['language']) {
			$this->language = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
		}
		
		// get mail object for sending confirmation messages
		$this->mail = t3lib_div::makeInstance('tx_sfmailsubscription_mail', $this);
		
		// get user object
		$this->user = t3lib_div::makeInstance('tx_sfmailsubscription_user', $this);
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
	protected function generateFields($type = 'create') {
		t3lib_div::loadTCA($this->conf['table']);

		// get subpart of all fields
		$subpartArray['###FIELDS_'.strtoupper($type).'###'] = $this->cObj->getSubpart($this->template['total'], '###FIELDS_'.strtoupper($type).'###');

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
		$markerArray['###ACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$markerArray['###FIELDNAME_SUBMIT###'] = $this->prefixId . '[submit]';
		$markerArray['###LABEL_SUBMIT###'] = $this->pi_getLL('label_submit');
		$markerArray['###CATEGORIES###'] = $this->generateCategories();
		$markerArray['###EDIT_LINK###'] = $this->cObj->typoLink('edit', array(
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => '&showEdit=1'
		));
		$markerArray['###HIDDEN_FIELDS###'] = '
			<input type="hidden" name="' . $this->prefixId . '[action]" value="'.$type.'" />
			<input type="hidden" name="' . $this->prefixId . '[uid]" value="'.intval(t3lib_div::_GET('user')).'" />
		';
		
		return $this->cObj->substituteMarkerArray($subpartArray['###FIELDS_'.strtoupper($type).'###'], $markerArray);
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
	
	function generateCategories() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'sys_dmail_category',
			'pid = ' . $this->conf['pid'] . $this->cObj->enableFields('sys_dmail_category'),
			'sorting', '', ''
		);
		
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			while($category = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->cObj->data = $category;
				$content .= $this->cObj->COBJ_ARRAY($this->conf['categories.']);
			}
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