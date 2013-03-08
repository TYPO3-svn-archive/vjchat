<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA["tx_vjchat_entry"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_entry",		
		"label" => "entry",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY tstamp DESC",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_vjchat_entry.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, entry, feuser, room, style",
	)
);

$TCA["tx_vjchat_room"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room",		
		"label" => "name",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"sortby" => "sorting",
		"default_sortby" => "ORDER BY crdate",	
		"delete" => "deleted",
		"dividers2tabs"	=> "1",
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",	
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_vjchat_room.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, starttime, endtime, fe_group, name, description, closed, owner, moderators, experts, groupaccess, maxusercount, bannedusers, welcomemessage, showuserinfo_experts, showuserinfo_moderators, showuserinfo_users, showuserinfo_superusers, members, private, page, chatUserNameFieldSuperusers, chatUserNameFieldExperts, chatUserNameFieldModerators, chatUserNameFieldUsers",
	)
);

$TCA["tx_vjchat_session"] = Array (
    "ctrl" => Array (
        "title" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session",        
        "label" => "name",    
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
		"sortby" => "sorting",		
        "default_sortby" => "ORDER BY crdate",    
        "delete" => "deleted",    
        "enablecolumns" => Array (        
            "disabled" => "hidden",
        ),
        "dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
        "iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_vjchat_session.gif",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, name, description, startid, endid",
    )
);

//adding sysfolder icon
t3lib_div::loadTCA('pages');
$TCA['pages']['columns']['module']['config']['items'][$_EXTKEY]['0'] = 'LLL:EXT:vjchat/locallang_db.php:tx_vjchat.sysfolder'; 
$TCA['pages']['columns']['module']['config']['items'][$_EXTKEY]['1'] = $_EXTKEY;

t3lib_div::loadTCA('tt_content');
$TCA["tt_content"]["types"]["list"]["subtypes_excludelist"][$_EXTKEY."_pi1"]="layout,select_key";
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
t3lib_extMgm::addPlugin(Array('LLL:EXT:vjchat/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:vjchat/flexform_ds.xml');

//t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","Chat");
if (TYPO3_MODE=='BE')	{
	include_once(t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_vjchat_itemsProcFunc.php');
	// Adds wizard icon to the content element wizard.
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_vjchat_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_vjchat_pi1_wizicon.php';

}

// extends fe_users
if (!defined ('TYPO3_MODE'))     die ('Access denied.');
$tempColumns = Array (
    "tx_vjchat_chatstyle" => Array (        
        "exclude" => 1,        
        "label" => "LLL:EXT:vjchat/locallang_db.php:fe_users.tx_vjchat_chatstyle",        
        "config" => Array (
            "type" => "input",    
            "size" => "30",
        )
    ),
);


t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_vjchat_chatstyle;;;;1-1-1");

include_once(t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_vjchat_itemsProcFunc.php');

?>