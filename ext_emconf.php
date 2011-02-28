<?php

########################################################################
# Extension Manager/Repository config file for ext "sfmailsubscription".
#
# Auto generated 28-02-2011 09:09
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'SF Mailsubscription',
	'description' => 'With this extension you can (un)subscribe to Newslettersystems like direct_mail. It\'s easy to use and to configure.',
	'category' => 'plugin',
	'author' => 'Stefan Froemken',
	'author_email' => 'firma@sfroemken.de',
	'shy' => '',
	'dependencies' => 'direct_mail',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'direct_mail' => '2.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:19:{s:9:"ChangeLog";s:4:"087d";s:10:"README.txt";s:4:"ee2d";s:16:"ext_autoload.php";s:4:"d828";s:21:"ext_conf_template.txt";s:4:"9f32";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"9297";s:14:"ext_tables.php";s:4:"158c";s:16:"locallang_db.xml";s:4:"79a2";s:19:"doc/wizard_form.dat";s:4:"95f1";s:20:"doc/wizard_form.html";s:4:"e6c9";s:40:"pi1/class.tx_sfmailsubscription_mail.php";s:4:"bfa4";s:39:"pi1/class.tx_sfmailsubscription_pi1.php";s:4:"24ae";s:40:"pi1/class.tx_sfmailsubscription_user.php";s:4:"8e46";s:19:"pi1/flexform_ds.xml";s:4:"27e8";s:17:"pi1/locallang.xml";s:4:"8765";s:12:"res/main.css";s:4:"4122";s:21:"res/pi1_template.html";s:4:"519a";s:39:"static/sfmailsubscription/constants.txt";s:4:"60ce";s:35:"static/sfmailsubscription/setup.txt";s:4:"d9a8";}',
	'suggests' => array(
	),
);

?>