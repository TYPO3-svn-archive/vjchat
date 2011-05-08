<?php

########################################################################
# Extension Manager/Repository config file for ext: "vjchat"
#
# Auto generated 08-05-2011 17:08
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'AJAX Chat',
	'description' => 'A rich featured ajax chat for TYPO3.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.3.3',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Vincent Tietz',
	'author_email' => 'vincent.tietz@vj-media.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:86:{s:9:"ChangeLog";s:4:"0a44";s:10:"README.txt";s:4:"ee2d";s:21:"ext_conf_template.txt";s:4:"563e";s:12:"ext_icon.gif";s:4:"8517";s:17:"ext_localconf.php";s:4:"88aa";s:14:"ext_tables.php";s:4:"f88e";s:14:"ext_tables.sql";s:4:"e98c";s:24:"ext_typoscript_setup.txt";s:4:"610f";s:15:"flexform_ds.xml";s:4:"8fa7";s:24:"icon_tx_vjchat_entry.gif";s:4:"83a0";s:23:"icon_tx_vjchat_room.gif";s:4:"8f24";s:26:"icon_tx_vjchat_session.gif";s:4:"883b";s:9:"index.php";s:4:"cf10";s:13:"locallang.php";s:4:"7ba8";s:16:"locallang_db.php";s:4:"38ee";s:7:"tca.php";s:4:"cf33";s:14:"doc/manual.sxw";s:4:"d5a5";s:19:"doc/wizard_form.dat";s:4:"9672";s:20:"doc/wizard_form.html";s:4:"310e";s:14:"pi1/ce_wiz.gif";s:4:"02b6";s:17:"pi1/chat.tpl.html";s:4:"c3d1";s:28:"pi1/class.tx_vjchat_chat.php";s:4:"d84a";s:26:"pi1/class.tx_vjchat_db.php";s:4:"096f";s:29:"pi1/class.tx_vjchat_entry.php";s:4:"e687";s:37:"pi1/class.tx_vjchat_itemsProcFunc.php";s:4:"70d1";s:27:"pi1/class.tx_vjchat_lib.php";s:4:"da83";s:31:"pi1/class.tx_vjchat_message.php";s:4:"aaeb";s:27:"pi1/class.tx_vjchat_pi1.php";s:4:"82e1";s:35:"pi1/class.tx_vjchat_pi1_wizicon.php";s:4:"db0a";s:28:"pi1/class.tx_vjchat_room.php";s:4:"7aa5";s:31:"pi1/class.tx_vjchat_session.php";s:4:"4a4a";s:37:"pi1/class.tx_vjchat_userFunctions.php";s:4:"3ba3";s:16:"pi1/fe_index.php";s:4:"a3cb";s:17:"pi1/locallang.php";s:4:"0e13";s:28:"pi1/tx_vjchat_pi1_js_chat.js";s:4:"701a";s:27:"pi1/tx_vjchat_pi1_js_lib.js";s:4:"b800";s:36:"pi1/tx_vjchat_pi1_js_soundsupport.js";s:4:"69c0";s:23:"pi1/emoticons/arrow.gif";s:4:"03a8";s:25:"pi1/emoticons/badgrin.gif";s:4:"c260";s:25:"pi1/emoticons/biggrin.gif";s:4:"293a";s:26:"pi1/emoticons/confused.gif";s:4:"90fc";s:22:"pi1/emoticons/cool.gif";s:4:"a557";s:21:"pi1/emoticons/cry.gif";s:4:"a7d5";s:23:"pi1/emoticons/doubt.gif";s:4:"429d";s:22:"pi1/emoticons/evil.gif";s:4:"d247";s:25:"pi1/emoticons/exclaim.gif";s:4:"32e9";s:22:"pi1/emoticons/idea.gif";s:4:"a620";s:21:"pi1/emoticons/lol.gif";s:4:"0172";s:21:"pi1/emoticons/mad.gif";s:4:"3170";s:25:"pi1/emoticons/neutral.gif";s:4:"9568";s:26:"pi1/emoticons/question.gif";s:4:"9281";s:22:"pi1/emoticons/razz.gif";s:4:"be49";s:25:"pi1/emoticons/redface.gif";s:4:"f41c";s:26:"pi1/emoticons/rolleyes.gif";s:4:"7bc8";s:21:"pi1/emoticons/sad.gif";s:4:"7cb7";s:23:"pi1/emoticons/shock.gif";s:4:"a9ce";s:23:"pi1/emoticons/smile.gif";s:4:"2640";s:27:"pi1/emoticons/surprised.gif";s:4:"bd90";s:22:"pi1/emoticons/wink.gif";s:4:"cba5";s:19:"pi1/icons/Thumbs.db";s:4:"c2be";s:28:"pi1/icons/icon_autofocus.gif";s:4:"bdda";s:31:"pi1/icons/icon_autofocus_on.gif";s:4:"5db4";s:20:"pi1/icons/icon_b.gif";s:4:"e5b7";s:24:"pi1/icons/icon_clock.gif";s:4:"6d71";s:27:"pi1/icons/icon_clock_on.gif";s:4:"a218";s:25:"pi1/icons/icon_colors.gif";s:4:"2d28";s:28:"pi1/icons/icon_colors_on.gif";s:4:"e10f";s:24:"pi1/icons/icon_email.gif";s:4:"9e8b";s:30:"pi1/icons/icon_enablesound.gif";s:4:"d2b7";s:33:"pi1/icons/icon_enablesound_on.gif";s:4:"8fda";s:23:"pi1/icons/icon_help.gif";s:4:"5a40";s:23:"pi1/icons/icon_http.gif";s:4:"16c4";s:20:"pi1/icons/icon_i.gif";s:4:"e6cc";s:28:"pi1/icons/icon_newwindow.gif";s:4:"1280";s:20:"pi1/icons/icon_s.gif";s:4:"51a5";s:26:"pi1/icons/icon_smilies.gif";s:4:"8119";s:29:"pi1/icons/icon_smilies_on.gif";s:4:"355a";s:20:"pi1/icons/icon_u.gif";s:4:"8b85";s:23:"pi1/icons/icon_user.gif";s:4:"58e4";s:29:"pi1/icons/icon_usercolors.gif";s:4:"af60";s:32:"pi1/icons/icon_usercolors_on.gif";s:4:"222d";s:29:"pi1/soundmanager2/license.txt";s:4:"d279";s:35:"pi1/soundmanager2/soundmanager2.swf";s:4:"cc3f";s:47:"pi1/soundmanager2/script/soundmanager2-jsmin.js";s:4:"4506";s:22:"pi1/sounds/Message.mp3";s:4:"bfdf";s:29:"pi1/sounds/UserlistChange.mp3";s:4:"06ee";}',
);

?>