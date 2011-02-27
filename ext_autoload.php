<?php
$extensionClassesPath = t3lib_extMgm::extPath('sfmailsubscription') . 'pi1/';
return array(
	'tx_sfmailsubscription_pi1' => $extensionClassesPath . 'class.tx_sfmailsubscription_pi1.php',
	'tx_sfmailsubscription_mail' => $extensionClassesPath . 'class.tx_sfmailsubscription_mail.php',
	'tx_sfmailsubscription_user' => $extensionClassesPath . 'class.tx_sfmailsubscription_user.php',
);
?>