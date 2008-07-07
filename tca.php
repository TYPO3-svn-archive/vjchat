<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_vjchat_entry"] = Array (
	"ctrl" => $TCA["tx_vjchat_entry"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,entry,feuser,room,style"
	),
	"feInterface" => $TCA["tx_vjchat_entry"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"entry" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_entry.entry",		
			"config" => Array (
				"type" => "text",	
				"eval" => "required",
			)
		),
		"style" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_entry.style",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
			)
		),		
		"feuser" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_entry.feuser",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"room" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_entry.room",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "tx_vjchat_room",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, entry, style, feuser, room")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_vjchat_room"] = Array (
	"ctrl" => $TCA["tx_vjchat_room"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,starttime,endtime,fe_group,name,description,welcomemessage,mode,showfullnames,closed,maxusercount,owner,moderators,experts,bannedusers,superusergroup,groupaccess,members,private"
	),
	"feInterface" => $TCA["tx_vjchat_room"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"description" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.description",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"maxusercount" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.maxusercount",        
            "config" => Array (
                "type" => "input",
                "size" => "4",
                "max" => "4",
                "eval" => "int",
                "checkbox" => "0",
                "range" => Array (
                    "upper" => "1000",
                    "lower" => "1"
                ),
                "default" => "50",
            )
        ),
		"closed" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.closed",		
			"config" => Array (
				"type" => "check",
			)
		),
        "mode" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.mode",        
            "config" => Array (
                "type" => "select",
                "items" => Array (
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.mode.I.0", "0"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.mode.I.1", "1"),
                ),
                "size" => 1,    
                "maxitems" => 1,
            )
        ),
        "showfullnames" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showfullnames",        
            "config" => Array (
                "type" => "check",
            )
        ),
/*		"moderators" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.moderators",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "fe_users",
				"itemsProcFunc" => "tx_vjchat_itemsProcFunc->user_vjchat_getFeUser",
				"foreign_table_where" => "ORDER BY fe_users.username",
				"size" => 20,	
				"minitems" => 0,
				"maxitems" => 100,
			)
		),
		
        "experts" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.experts",        
            "config" => Array (
                "type" => "select",    
                "foreign_table" => "fe_users",    
                "foreign_table_where" => "ORDER BY fe_users.username",    
				"itemsProcFunc" => "tx_vjchat_itemsProcFunc->user_vjchat_getFeUser",
                "size" => 20,    
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
        "bannedusers" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.bannedusers",        
            "config" => Array (
                "type" => "select",    
                "foreign_table" => "fe_users",    
                "foreign_table_where" => "ORDER BY fe_users.username",    
				"itemsProcFunc" => "tx_vjchat_itemsProcFunc->user_vjchat_getFeUser",
                "size" => 20,    
                "minitems" => 0,
                "maxitems" => 100,
            )
        ),
		
*/
        "private" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.private",        
            "config" => Array (
                "type" => "check",
            )
        ),
		"owner" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.owner",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"moderators" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.moderators",		
			"config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'fe_users',
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 100,
			)
		),
		"experts" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.experts",		
			"config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'fe_users',
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 100,
			)
		),
		"bannedusers" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.bannedusers",		
			"config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'fe_users',
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 100,
			)
		),
		"members" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.members",		
			"config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'fe_users',
				"size" => 10,	
				"minitems" => 0,
				"maxitems" => 100,
			)
		),
		"groupaccess" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.groupaccess",        
            "config" => Array (
				"type" => "select",	
				"foreign_table" => "fe_groups",	
				"foreign_table_where" => "ORDER BY fe_groups.title",	
                "size" => 10,    
                "minitems" => 0,
                "maxitems" => 100,
            )
		),
		"superusergroup" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.superusergroup",        
            "config" => Array (
				"type" => "select",	
				"foreign_table" => "fe_groups",	
				"foreign_table_where" => "ORDER BY fe_groups.title",	
                "size" => 10,    
                "minitems" => 0,
                "maxitems" => 100,
            )
		),		
        "welcomemessage" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.welcomemessage",        
            "config" => Array (
                "type" => "text",
                "cols" => "30",    
                "rows" => "5",
            )
        ),

		
		"showuserinfo_experts" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_experts",        
            "config" => Array (
                "type" => "input",
                "default" => "name,company",
            )
        ),
		
		/*
        "showuserinfo_experts" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_experts",        
            "config" => Array (
                "type" => "select",    
				"itemsProcFunc" => "tx_vjchat_itemsProcFunc->user_vjchat_getFeUserColumns",
                "size" => 10,    
                "minitems" => 0,
                "maxitems" => 100,
				"default" => 1,
            )
        ),
		*/
		"showuserinfo_moderators" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_moderators",        
            "config" => Array (
                "type" => "input",
                "default" => "name,company",
            )
        ),
		"showuserinfo_users" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_users",        
            "config" => Array (
                "type" => "input",
                "default" => "",
            )
        ),

		"showuserinfo_superusers" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_superusers",        
            "config" => Array (
                "type" => "input",
                "default" => "",
            )
        ),
		
		"page" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.page",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "pages",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"image" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:vjchat/locallang_db.xml:tx_vjchat_room.image",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
				"max_size" => 500,	
				"uploadfolder" => "uploads/tx_vjchat",
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),		

/*		"showuserinfo_experts" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_experts",        
            "config" => Array (
                "type" => "check",
                "cols" => 4,
                "items" => Array (
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_name.I.0", "1"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_email.I.1", "2"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_company.I.2", "3"),
                ),
            )
        ),
        "showuserinfo_moderators" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_moderators",        
            "config" => Array (
                "type" => "check",
                "cols" => 4,
                "items" => Array (
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_name.I.0", "1"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_email.I.1", "2"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_company.I.2", "3"),
                ),
            )
        ),
        "showuserinfo_users" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_users",        
            "config" => Array (
                "type" => "check",
                "cols" => 4,
                "items" => Array (
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_name.I.0", "1"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_email.I.1", "2"),
                    Array("LLL:EXT:vjchat/locallang_db.php:tx_vjchat_room.showuserinfo_company.I.2", "3"),
                ),
            )
        ),		*/
    ),

    "types" => Array (
        "0" => Array("showitem" => "--div--;General,hidden;;1;;1-1-1, name;;2;;2-2-2, description, welcomemessage, mode, showfullnames, closed, page;;3;;3-3-3, maxusercount, image, --div--;Users,moderators;;4;;4-4-4, experts, groupaccess, superusergroup, bannedusers;;5;;5-5-5, showuserinfo_experts;;6;;6-6-6, showuserinfo_moderators, showuserinfo_users, showuserinfo_superusers,--div--;Private Room,private,owner,members")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "starttime, endtime, fe_group")
    )
);


$TCA["tx_vjchat_session"] = Array (
    "ctrl" => $TCA["tx_vjchat_session"]["ctrl"],
    "interface" => Array (
        "showRecordFieldList" => "hidden,starttime,endtime,name,description,startid,endid,room"
    ),
    "feInterface" => $TCA["tx_vjchat_session"]["feInterface"],
    "columns" => Array (
        "hidden" => Array (        
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
            "config" => Array (
                "type" => "check",
                "default" => "0"
            )
        ),
        "starttime" => Array (        
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
            "config" => Array (
                "type" => "input",
                "size" => "8",
                "max" => "20",
                "eval" => "date",
                "default" => "0",
                "checkbox" => "0"
            )
        ),
        "endtime" => Array (        
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
            "config" => Array (
                "type" => "input",
                "size" => "8",
                "max" => "20",
                "eval" => "date",
                "checkbox" => "0",
                "default" => "0",
                "range" => Array (
                    "upper" => mktime(0,0,0,12,31,2020),
                    "lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
                )
            )
        ),
        "name" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session.name",        
            "config" => Array (
                "type" => "input",    
                "size" => "30",
				"eval" => "required",
            )
        ),
        "description" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session.description",        
            "config" => Array (
                "type" => "text",
                "cols" => "30",    
                "rows" => "5",
            )
        ),
        "startid" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session.startid",        
            "config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'tx_vjchat_entry',
				"size" => 1,	
				"minitems" => 1,
				"maxitems" => 1,
				"required" => 1,
            )
        ),
        "endid" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session.endid",        
            "config" => Array (
				"type" => "group",
                "internal_type" => "db", 					
				"allowed" => 'tx_vjchat_entry',
				"size" => 1,	
				"minitems" => 1,
				"maxitems" => 1,
				"required" => 1,
            )
        ),
        "room" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:vjchat/locallang_db.php:tx_vjchat_session.room",        
            "config" => Array (
                "type" => "select",    
                "foreign_table" => "tx_vjchat_room",    
                "foreign_table_where" => "ORDER BY tx_vjchat_room.name",    
                "size" => 1,    
                "minitems" => 0,
                "maxitems" => 1,
                "eval" => "required",
            )
        ),
    ),
    "types" => Array (
        "0" => Array("showitem" => "hidden;;1;;1-1-1, name, description, startid, endid, room")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "starttime, endtime")
    )
);

if (TYPO3_MODE == 'BE')	{
	$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vjchat']);
	if($conf['showRealNamesListing']) {
		$TCA['tx_vjchat_room']['columns']['experts']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';
		$TCA['tx_vjchat_room']['columns']['moderators']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';
		$TCA['tx_vjchat_room']['columns']['bannedusers']['config']['itemsProcFunc'] = 'tx_vjchat_itemsProcFunc->user_vjchat_getFeUser';				
	}
}
?>