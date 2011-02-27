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
class tx_sfmailsubscription_mail {
	/**
	 * parent Object
	 *
	 * @var tx_sfmailsubscription_pi1
	 */
	var $pObj;
		
	/**
	 * Swiftmail-Object
	 *
	 * @var t3lib_mail_Message
	 */
	var $mail;
	
	function __construct($conf) {
		$this->pObj = $conf;
	}
	
	/**
	 * send mail to user
	 * 
	 * @param integer $uid UID of the User-Table
	 */
	public function sendMail($type = 'create') {
		$this->mail = t3lib_div::makeInstance('t3lib_mail_message');
		$this->mail->setFrom(array($this->pObj->extConf['from_email'] => $this->pObj->extConf['from_name']));
		$this->mail->setTo(array($this->pObj->userArray['email'] => $this->pObj->userArray['name']));
		$this->mail->setSubject($this->pObj->conf['subject']);

		$this->setMailText($type);

		$this->mail->send();
	}
	
	/**
	 * Enter description here ...
	 * @return string
	 */
	protected function createLink() {
		$link = $this->pObj->pi_getPageLink(
			$GLOBALS['TSFE']->id,
			'',
			array(
				'no_cache' => 1,
				'user' => $this->pObj->userArray['uid'],
				'authCode' => $this->getAuthCode()
			)
		);
		return t3lib_div::locationHeaderUrl($link);
	}

	/**
	 * Generate an authCode to validate confirmation
	 * @return string AuthCode
	 */
	public function getAuthCode() {
		return t3lib_div::stdAuthCode($this->pObj->userArray, 'uid');
	}
	
	/**
	 * Generate an authCode to validate confirmation
	 * @return string AuthCode
	 */
	public function setMailText($marker, $type = 'both') {
		// define additional Markers
		$markerArray['###URL###'] = $this->createLink();
		$markerArray['###SERVER###'] = t3lib_div::getIndpEnv('HTTP_HOST');
		foreach($this->pObj->userArray as $key => $value) {
			$markerArray['###USER_' . strtoupper($key) . '###'] = $this->pObj->cObj->stdWrap($value, $this->pObj->conf['userFields.'][$key . '.']);
			$markerArray['###USER_' . strtoupper($key) . '_PLAIN###'] = strip_tags($this->pObj->cObj->stdWrap($value, $this->pObj->conf['userFields.'][$value]));
		}
		
		// get Template for HTML-Mails
		$subpart['html'] = $this->pObj->cObj->getSubpart(
			$this->pObj->template['total'],
			'###EMAIL_' . strtoupper($marker) . '_HTML'
		);
		$subpart['html'] = $this->pObj->cObj->substituteMarkerArray($subpart['html'], $markerArray);
		
		// get Template for PLAIN-Mails
		$subpart['plain'] = $this->pObj->cObj->getSubpart(
			$this->pObj->template['total'],
			'###EMAIL_' . strtoupper($marker) . '_PLAIN'
		);
		$subpart['plain'] = $this->pObj->cObj->substituteMarkerArray($subpart['plain'], $markerArray);
		
		switch($type) {
			case 'html':
				$this->mail->setBody($subpart['html'], 'text/html');
				break;			
			case 'plain':
				$this->mail->setBody($subpart['plain'], 'text/plain');
				break;			
			case 'default':
			case 'both':
				$this->mail->setBody($subpart['html'], 'text/html');
				$this->mail->addPart($subpart['plain'], 'text/plain');
				break;			
		}
		return $subpart;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_mail.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sfmailsubscription/pi1/class.tx_sfmailsubscription_mail.php']);
}
?>