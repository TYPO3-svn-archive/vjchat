<?php

//require_once(PATH_t3lib.'class.t3lib_div.php');

class tx_vjchat_room {

	var $uid;
	var $pid;
	var $hidden;
	var $fe_group;
	var $name;
	var $description;
	var $welcomemessage;
	var $closed;
	var $mode;
	var $maxusercount;
	var $owner;
	var $moderators;
	var $experts;
	var $groupaccess;
	var $superusergroup;	
	var $sessions;
	var $bannedusers;
	var $showuserinfo_experts;
	var $showuserinfo_moderators;
	var $showuserinfo_users;
	var $showuserinfo_superusers;
	var $private;
	var $members;
	var $page;
	var $image;
	var $chatUserNameFieldSuperusers;
	var $chatUserNameFieldExperts;
	var $chatUserNameFieldModerators;
	var $chatUserNameFieldUsers;

	function tx_vjchat_room() {
	
	}
	

	function fromArray($array) {
		$this->uid = intval($array['uid']);
		$this->pid = intval($array['pid']);
		$this->hidden = $array['hidden'] ?  true : false;
		$this->fe_group = $array['fe_group'];

		$this->name = $array['name'];
		$this->description = $array['description'];
		$this->closed = $array['closed'];	
		$this->mode = $array['mode'];					
		$this->maxusercount = $array['maxusercount'];
		$this->moderators = $array['moderators'];	
		$this->owner = $array['owner'];	
		$this->experts = $array['experts'];
		$this->groupaccess = $array['groupaccess'];	
		$this->superusergroup = $array['superusergroup'];			
		$this->bannedusers = $array['bannedusers'];
		$this->showuserinfo_experts = $array['showuserinfo_experts'];	
		$this->showuserinfo_moderators = $array['showuserinfo_moderators'];	
		$this->showuserinfo_users = $array['showuserinfo_users'];	
		$this->showuserinfo_superusers = $array['showuserinfo_superusers'];
		$this->welcomemessage = $array['welcomemessage'];							
		$this->private = $array['private'] ? true : false;
		$this->members = $array['members'];											
		$this->page = $array['page'];
		$this->image = $array['image'];		
		$this->chatUserNameFieldSuperusers = $array['chatUserNameFieldSuperusers'];
		$this->chatUserNameFieldExperts = $array['chatUserNameFieldExperts'];
		$this->chatUserNameFieldModerators = $array['chatUserNameFieldModerators'];
		$this->chatUserNameFieldUsers = $array['chatUserNameFieldUsers'];
		
	}

	function toArray() {

		$theValue = array(
			'uid' => intval($this->uid),
			'pid' => intval($this->pid),
			'hidden' => $this->hidden ? 1 : 0,						
			'fe_group' => $this->fe_group,			
			'name' => $this->name,
			'description' => $this->description,
			'closed' => $this->closed,
			'mode' => $this->mode,			
			'maxusercount' => $this->maxusercount,			
			'owner' => $this->owner,
			'moderators' => $this->moderators,
			'experts' => $this->experts,
			'groupaccess' => $this->groupaccess,
			'superusergroup' => $this->superusergroup,			
			'bannedusers' => $this->bannedusers,
			'showuserinfo_experts' => $this->showuserinfo_experts,
			'showuserinfo_moderators' => $this->showuserinfo_moderators,
			'showuserinfo_users' => $this->showuserinfo_users,
			'showuserinfo_superusers' => $this->showuserinfo_superusers,
			'welcomemessage' => $this->welcomemessage,									
			'private' => $this->private ? 1 : 0,												
			'members' => $this->members,												
			'page' => $this->page,
			'image' => $this->image,
			'chatUserNameFieldSuperusers' => $this->chatUserNameFieldSuperusers,
			'chatUserNameFieldExperts' => $this->chatUserNameFieldExperts,
			'chatUserNameFieldModerators' => $this->chatUserNameFieldModerators,
			'chatUserNameFieldUsers' => $this->chatUserNameFieldUsers
		);

		return $theValue;
	}

	function isExpertMode() {
		return ($this->mode == 1);
	}

	function isClosed() {
		return ($this->closed == 1);
	}

	function isPrivate() {
		return ($this->private == 1);
	}
	
	function showDetailOf($type, $what) {
		switch($type) {
			case 'user': 
				return t3lib_div::inList($this->showuserinfo_users, $what);
			case 'expert': 
				return t3lib_div::inList($this->showuserinfo_experts, $what);
			case 'moderator': 
				return t3lib_div::inList($this->showuserinfo_moderators, $what);
			case 'superuser': 
				return t3lib_div::inList($this->showuserinfo_superusers, $what);
			default:
				return false;
		}
	}
	
	function getDetailsField($type) {
		switch($type) {
			case 'user': 
				return t3lib_div::trimExplode(',',$this->showuserinfo_users);
			case 'expert': 
				return t3lib_div::trimExplode(',',$this->showuserinfo_experts);
			case 'moderator': 
				return t3lib_div::trimExplode(',',$this->showuserinfo_moderators);
			case 'superuser': 
				return t3lib_div::trimExplode(',',$this->showuserinfo_superusers);
			default:
				return array();
		}
	}

/*	function getModeratorIDs() {
		$moderators = array();

		foreach ($this->moderators as $moderator)
			$moderators[] = $moderator['uid'];

		return implode(',',$moderators);
				
	}	
*/
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_room.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_room.php']);
}

?>