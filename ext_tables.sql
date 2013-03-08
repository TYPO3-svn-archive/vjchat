#
# Table structure for table 'tx_vjchat_entry'
#
CREATE TABLE tx_vjchat_entry (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	entry text NOT NULL,
	feuser blob NOT NULL,
	tofeuser int(11) DEFAULT '0' NOT NULL,	
	room blob NOT NULL,
    style tinyint(4) unsigned DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_vjchat_room'
#
CREATE TABLE tx_vjchat_room (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,	
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	name tinytext NOT NULL,
	description text NOT NULL,
	maxusercount int(11) DEFAULT '0' NOT NULL,
	closed tinyint(3) DEFAULT '0' NOT NULL,
 	mode tinyint(3) DEFAULT '0' NOT NULL,
	moderators blob NOT NULL,
    experts blob NOT NULL,
    groupaccess blob NOT NULL,
	superusergroup blob NOT NULL,
    bannedusers blob NOT NULL,
    welcomemessage text NOT NULL,
    showuserinfo_experts blob NOT NULL,
    showuserinfo_moderators blob NOT NULL,
    showuserinfo_users blob NOT NULL,
	showuserinfo_superusers blob NOT NULL,
	chatUserNameFieldSuperusers tinytext NOT NULL,
	chatUserNameFieldExperts tinytext NOT NULL,
	chatUserNameFieldModerators tinytext NOT NULL,
	chatUserNameFieldUsers tinytext NOT NULL,	
	page blob NOT NULL,
	image blob NOT NULL,
	owner int(11) DEFAULT '0' NOT NULL,
	private tinyint(4) DEFAULT '0' NOT NULL,	 		
	members blob NOT NULL,	
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_vjchat_session'
#
CREATE TABLE tx_vjchat_session (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,	
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    starttime int(11) DEFAULT '0' NOT NULL,
    endtime int(11) DEFAULT '0' NOT NULL,
    name tinytext NOT NULL,
    description text NOT NULL,
    startid int(11) DEFAULT '0' NOT NULL,
    endid int(11) DEFAULT '0' NOT NULL,
    room int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);


CREATE TABLE tx_vjchat_room_feusers_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  invisible tinyint(4) unsigned DEFAULT '0' NOT NULL,
  in_room tinyint(4) unsigned DEFAULT '0' NOT NULL,
  userlistsnippet text NOT NULL,
  tooltipsnippet text NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
    tx_vjchat_chatstyle tinyint(4) unsigned DEFAULT '0' NOT NULL,
);