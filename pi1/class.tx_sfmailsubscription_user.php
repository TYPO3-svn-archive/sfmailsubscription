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

/**
 * Plugin 'SF Mailsubscription' for the 'sfmailsubscription' extension.
 *
 * @author	Stefan Froemken <firma@sfroemken.de>
 * @package	TYPO3
 * @subpackage	tx_sfmailsubscription
 */
class tx_sfmailsubscription_user {
	var $disabledField = '';
	/**
	 * parent Object
	 *
	 * @var tx_sfmailsubscription_pi1
	 */
	var $pObj;
	
	function __construct($pObj) {
		$this->pObj = $pObj;
		$this->disabledField = $GLOBALS['TCA'][$this->pObj->conf['table']]['ctrl']['enablecolumns']['disabled'];
	}
	
	/**
	 * try to gets user array
	 * 
	 * @param int/string $value user's email or uid
	 * @return array Array with userdata
	 */
	function getUserRecord($value = 0) {
		if($value == 0) {
			// If $value is not set, try to get an userID from GET
			$uid = intval(t3lib_div::_GET('user'));
			if(!$uid) {
				// if we can get an userID, try it with the emailaddress
				$email = htmlspecialchars($this->pObj->piVars['email']);
				$where = 'email = "' . $email . '"';			
			} else {
				$where = 'uid = ' . $uid;			
			}
		} elseif(is_int($value) && $value > 0) {
			// If $value is set/int and greater then 0
			$where = 'uid = ' . $value;	
		} else {
			// If $value is string, check if $value = email
			if(t3lib_div::validEmail($value)) {
				$where = 'email = "' . htmlspecialchars($value) . '"';			
			}
		}
		
		if($where != '') {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->pObj->conf['table'],
				$where .
				$this->pObj->cObj->enableFields($this->pObj->conf['table'], 1),
				'', '', ''
			);
			
			//echo $res . "\n";
			//print_r($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res));
			
			if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
				return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			} else {
				return false;
			}			
		} else {
			return false;
		}
	}
	
	function createUser() {
		// if record was found decide if an error occours or the record should be updated
		if($user = $this->getUserRecord($this->pObj->piVars['email'])) {
			if($this->pObj->conf['updateUserIfPossible']) {
				$this->pObj->piVars['module_sys_dmail_newsletter'] = 1;
				$this->pObj->cObj->DBgetUpdate(
					$this->pObj->conf['table'],
					$user['uid'],
					array('module_sys_dmail_newsletter' => 1),
					'module_sys_dmail_newsletter', TRUE
				);
			} else {
				return $this->pObj->pi_getLL('error_allreadyInDB');
			}
		} else {
			// set some defaults for insertion
			$this->pObj->piVars['username'] = $this->pObj->piVars['email'];
			$this->pObj->piVars['password'] = substr(md5($this->piVars['email']), 0, 8);
			$this->pObj->piVars['usergroup'] = $this->pObj->conf['userGroup'];
			$this->pObj->piVars[$this->disabledField] = 1;
			$this->pObj->piVars['module_sys_dmail_newsletter'] = 1;
			$fieldListForTable = $this->disabledField . ',' . $this->pObj->conf['fieldList'] . ',' . $this->pObj->requiredFieldsForTable[$this->pObj->conf['table']];

			$res = $this->pObj->cObj->DBgetInsert($this->pObj->conf['table'], $this->pObj->conf['pid'], $this->pObj->piVars, $fieldListForTable, TRUE);
			$this->pObj->userArray = $this->getUserRecord($GLOBALS['TYPO3_DB']->sql_insert_id($res));
		}
		return true;
	}	

	/**
	 * Enter description here ...
	 * @return string|boolean
	 */
	function activateUser() {
		// if record was found decide if an error occours or the record should be updated
		if($user = $this->getUserRecord(intval(t3lib_div::_GET('user')))) {
			$this->pObj->cObj->DBgetUpdate(
				$this->pObj->conf['table'],
				$user['uid'],
				array($this->disabledField => 0),
				$this->disabledField, TRUE
			);
			return $GLOBALS['TYPO3_DB']->sql_affected_rows();			
		}
		return false;
	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_user.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_user.php']);
}
?>