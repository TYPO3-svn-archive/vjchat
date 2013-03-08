<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Vincent Tietz (vincent.tietz@vj-media.de)
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
 * Plugin 'Chat' for the 'vjchat' extension.
 *
 * @author	Vincent Tietz <vincent.tietz@vj-media.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once('class.tx_vjchat_db.php');
require_once('class.tx_vjchat_lib.php');

class tx_vjchat_pi1 extends tslib_pibase {
	var $prefixId = 'tx_vjchat_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_vjchat_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'vjchat';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	var $chatScript;
	
	var $db;	// Datasource
	
	var $user;
	var $debug = false;
	

	function main($content,$conf)	{
		$this->conf=$conf;

		$chatScript = 'index.php?eID=tx_vjchat_pi1';

		$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc'] = '
			<script language="JavaScript" type="text/javascript">
			//<![CDATA[

				function tx_vjchat_openNewChatWindow(url, chatId) {
					var concatinator = "&";
					if(url.indexOf("?") == -1)
						var concatinator = "?";
					var vHWindow = window.open(url+concatinator+"tx_vjchat_pi1[uid]="+chatId+"&tx_vjchat_pi1[view]=chat&tx_vjchat_pi1[popup]=1","chatwindow"+chatId,"'.$this->conf['chatPopupJSWindowParams'].'");
					vHWindow.focus();
				}		
			//]]>
			</script>
		';

		$this->chatScript = t3lib_div::getIndpEnv(TYPO3_REQUEST_DIR).$chatScript;
		

		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$GLOBALS['TSFE']->set_no_cache(); // disable frontend caching on this page

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
	
		$this->user = $GLOBALS['TSFE']->fe_user->user;

		$this->loadFLEX();		
		
		$this->debug = $this->piVars['debug'];
		
		$this->db = t3lib_div::makeInstance('tx_vjchat_db');
		$this->db->setDebug($this->debug);
		
		if($this->piVars['leaveRoom'] && $this->db->isMemberOfRoom($this->piVars['leaveRoom'], $this->user['uid'])) {
			$this->db->leaveRoom($this->piVars['leaveRoom'], $this->user['uid'], true, $this->pi_getLL('user_leaves_chat'));
		}
		
		
		if($action = $this->piVars['action']) 
			switch($action) {
				case 'delete': 
					$content = $this->deleteEntry($this->piVars['entryId']);
				break;
			}
		
		// dynamic view set in frontend
		if($view = $this->piVars['view'])
			switch($view) {
				case 'chat': 
					$content = $this->displayChatRoom($this->piVars['uid']);
					break;
				case 'sessions' :
					$content = $this->displaySessionsOfRoom($this->piVars['uid']);
					break;
				case 'session' :
					$content = $this->displaySession($this->piVars['uid']);
					break;
				
			}
		else
			// if nothing set use default view from FLEX form
			switch($this->conf['FLEX']['display']) {
				case 'rooms': $content = $this->displayRooms();
				break;
				case 'chat': $content = $this->displayChatRoom($this->conf['FLEX']['chatroom']);
				break;
				case 'overallusercount': $content = $this->displayOverallChatuserNumber();
				break;
			}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	/* gets configuration from plugin-flexform */
	function loadFLEX() {

		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'display', 'sDEF');
		$this->conf['FLEX']['display'] = $value ? $value : 'rooms';

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatroom', 'sDEF');
		$this->conf['FLEX']['chatroom'] = $value;
		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'initChatWithMessagesBefore', 'chatDEF');
		$this->conf['FLEX']['initChatWithMessagesBefore'] = $value ? $value : 10;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'reloadTimeIfRoomFull', 'chatDEF');
		$this->conf['FLEX']['reloadTimeIfRoomFull'] = $value ? $value : 30;
	
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'refreshMessagesTime', 'chatDEF');
		$this->conf['FLEX']['refreshMessagesTime'] = $value ? $value : 5;
	
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'refreshUserListTime', 'chatDEF');
		$this->conf['FLEX']['refreshUserListTime'] = $value ? $value : 15;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showFormatting', 'chatDEF');
		$this->conf['FLEX']['showFormatting'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showEmoticons', 'chatDEF');
		$this->conf['FLEX']['showEmoticons'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showStyles', 'chatDEF');
		$this->conf['FLEX']['showStyles'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatwindow', 'sDEF');
		$value = $value ? $value : $this->conf['defaultChatpopupPid'];
		$this->conf['FLEX']['chatwindow'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'targetwindow', 'sDEF');
		$value = $value ? $value : $this->conf['targetwindow'];
		$this->conf['FLEX']['targetwindow'] = $value;

		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'colorizeNicks', 'chatDEF');
		$this->conf['FLEX']['colorizeNicks'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showTime', 'chatDEF');
		$this->conf['FLEX']['showTime'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'enableSound', 'chatDEF');
		$this->conf['FLEX']['enableSound'] = $value;
		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showSendButton', 'chatDEF');
		$this->conf['FLEX']['showSendButton'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxUserCount', 'sDEF');
		$this->conf['FLEX']['maxUserCount'] = $value;		

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hideEmptyRooms', 'sDEF');
		$this->conf['FLEX']['hideEmptyRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hideClosedRooms', 'sDEF');
		$this->conf['FLEX']['hideClosedRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hidePrivateRooms', 'sDEF');
		$this->conf['FLEX']['hidePrivateRooms'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showSuperusers', 'sDEF');
		$this->conf['FLEX']['showSuperusers'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showModerators', 'sDEF');
		$this->conf['FLEX']['showModerators'] = $value;
		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showUsers', 'sDEF');
		$this->conf['FLEX']['showUsers'] = $value;
		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showExperts', 'sDEF');
		$this->conf['FLEX']['showExperts'] = $value;		

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showUserCount', 'sDEF');
		$this->conf['FLEX']['showUserCount'] = $value;		

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDescription', 'sDEF');
		$this->conf['FLEX']['showDescription'] = $value;		
		
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDescriptionInChat', 'chatDEF');
		$this->conf['FLEX']['showDescriptionInChat'] = $value;

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'typoscriptRoomsTemplate', 'sDEF');
		$this->conf['tsRooms'] = $value ? $value : 'rooms';	
		
		if($this->conf['tsRooms'] == 'custom') {
			$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'typoscriptRoomsTemplateCustom', 'sDEF');
			$this->conf['tsRooms'] = $value ? $value : 'rooms';		
		}

		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'chatrooms', 'sDEF');
		$this->conf['FLEX']['chatrooms'] = $value ? $value : null;		
		
		if(!$this->conf['FLEX']['chatrooms']) {
			$value = $this->cObj->data['pages'] ? $this->pi_getPidList($this->cObj->data['pages'], $this->cObj->data['recursive']) : null;
			$this->conf['pidList'] = $value ? $value : $this->conf['pidList'];
			
		}
	}
	
	function getRoomsFromFlexConf() {
		if(!$this->conf['FLEX']['chatrooms'])
			$rooms = $this->db->getRooms($this->conf['pidList']);
		else {
			$rooms = array();
			$roomsIds = t3lib_div::trimExplode(',', $this->conf['FLEX']['chatrooms']);
			foreach($roomsIds as $id) {
				$rooms[] = $this->db->getRoom($id);
			}
		}
	
		
	
		$theValue = array();
		foreach($rooms as $room) {

			$this->db->cleanUpUserInRoom($room->uid, 20, true, $this->pi_getLL('user_leaves_chat'));			
			
			if($this->conf['FLEX']['hideEmptyRooms'] && ($this->db->getUserCountOfRoom($room->uid) == 0))
				continue;
			
			if($this->conf['FLEX']['hideEmptyRooms'] && $room->isClosed())
				continue;

			if($this->conf['FLEX']['hidePrivateRooms'] && $room->isPrivate())
				continue;
					
			$theValue[] = $room;
			
		}
	
		return $theValue;
	
	}
	
	function displayRooms() {
		
		$rooms = $this->getRoomsFromFlexConf();
	
		foreach($rooms as $room) {

			// set data (current room array to cobj)
			$this->cObj->data = $this->getRoomData($room);
			
			if(!$this->conf['FLEX']['showDescription'])
				unset($this->cObj->data['description']);
			
			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.'][$this->conf['tsRooms'].'.']['oneRoom.']);
		}
		
		$this->cObj->data['popup'] = $this->piVars['popup'];

		return $this->cObj->stdWrap($theValue, $this->conf['views.'][$this->conf['tsRooms'].'.']['stdWrap.']);
		
	}
	
	function getSnippet($room, $user, $conf) {
		if(!$conf || !$user)
			return '';

		$cObj = t3lib_div::makeInstance('tslib_cObj');
			
			// this makes sure that only fields are available, that are defined in showuserinfo_users, ...
		$type = tx_vjchat_lib::getUserTypeString($room, $user);		
		$details = $room->getDetailsField($type);
		foreach($details as $key) {
			if($room->showDetailOf($type,$key))
				$cObj->data[$key] = $user[$key];
		}
		
			// these are always available
		$cObj->data['username'] = tx_vjchat_lib::getChatUserName($room, $user);
		$cObj->data['image'] = $user['image'];
		$cObj->data['uid'] = $user['uid'];
	
		return $cObj->cObjGet($conf);
	}
	
	function displayChatRoom($roomId) {

		$this->db->cleanUpRooms();
	
		if(!$room = $this->db->getRoom($roomId))
			return $this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['chat.']['stdWrap.']);

		// remove old message entries if set
		if($this->db->extCONF['autoDeleteEntries'])
			$this->db->deleteEntries($roomId, $this->db->extCONF['autoDeleteEntries']);

		$this->cObj->data = $this->getRoomData($room);

		if(!$this->conf['FLEX']['showDescriptionInChat'])
			unset($this->cObj->data['description']);

		if(!tx_vjchat_lib::isSuperuser($room, $this->user)) {

			if(tx_vjchat_lib::isBanned($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_banned'), $this->conf['views.']['chat.']['stdWrap.']);
	
			// check if user is kicked
			if($res = $this->db->isUserKicked($room->uid, $this->user['uid']))
				return $this->displayErrorMessage(sprintf($this->pi_getLL('error_kicked'),$res), $this->conf['views.']['chat.']['stdWrap.']);
	
			// check if this is a private room and if the user is an invited member
			if($room->private && !tx_vjchat_lib::isMember($room, $this->user['uid']))
				return $this->displayErrorMessage($this->pi_getLL('error_not_invited'), $this->conf['views.']['chat.']['stdWrap.']);
	
			// remove user who left room and remove system messages
			$this->db->cleanUpUserInRoom($room->uid, 20, true, $this->pi_getLL('user_leaves_chat'));
			
			//check rights to view room
			if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
				return $this->displayErrorMessage($this->pi_getLL('error_room_access_denied'), $this->conf['views.']['chat.']['stdWrap.']);

		}	


		
			// only include prototype if not used prototypejs
		if($this->db->extCONF['prototypeJSPath'] && !t3lib_extMgm::isLoaded('prototypejs'))
			$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc_chat'] .= '
			<script language="JavaScript" type="text/javascript" src="'.$this->db->extCONF['prototypeJSPath'].'"></script>
		';

		if($this->db->extCONF['scriptaculousJSPath'])
			$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc_chat'] .= '
			<script language="JavaScript" type="text/javascript" src="'.$this->db->extCONF['scriptaculousJSPath'].'"></script>
		';
		
		$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc_chat'] .= '
			<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'pi1/tx_vjchat_pi1_js_lib.js"></script>
			<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'pi1/tx_vjchat_pi1_js_chat.js"></script>
		';
		
		if($this->db->extCONF['soundSupport'] == 'jssoundkt') {
			
			$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc_chat'] .= '
			<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'pi1/tx_vjchat_pi1_js_soundsupport.js"></script>
			<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath('jssoundkt').'jssoundkt-0.1/javascripts/DP_Debug.js"></script>
			<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath('jssoundkt').'jssoundkt-0.1/javascripts/Sound.js"></script>
		';
		}
		

		if($this->db->extCONF['soundSupport'] == 'soundmanager2') {

			$GLOBALS['TSFE']->additionalHeaderData['tx_vjchat_inc_chat'] .= '
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'pi1/tx_vjchat_pi1_js_soundsupport.js"></script>
				<script language="JavaScript" type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'pi1/soundmanager2/script/soundmanager2-jsmin.js"></script>
			';
		
		
		}

		$template = $this->cObj->fileResource($this->conf['templateFile']);
		
		// there are two subparts: CHATROOM and CHATROOM_FULL
		// these markers can be used by both types
		$markerArray['###CHATROOM_NAME###'] = $room->name;
		$markerArray['###CHATROOM_ID###'] = $roomId;
		$markerArray['###SCRIPTURL###'] = $this->chatScript;
		$markerArray['###LEAVEURL###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, true);
		
		$markerArray['###NEWWINDOWURL###'] = $this->getNewWindowUrl();
			
		$markerArray['###DEBUG###'] = $this->debug ? $this->debug : 'false';
		
		$markerArray['###LLKEY###'] = $LLKey = $GLOBALS['TSFE']->config['config']['language'];
		
		$time = $this->db->getTime()-($this->conf['FLEX']['initChatWithMessagesBefore']*60);
		$initialid = $this->db->getLatestEntryId($room, $time);
		
		$markerArray['###INITIALID###'] = $initialid;
		$markerArray['###CHARSET###'] = $GLOBALS['TSFE']->renderCharset;

		$markerArray['###USERID###'] = $this->user['uid'];

		$markerArray['###USERCOLORS###'] = $this->getUserColorArray();

		$markerArray['###CSS_USERCOLORS###'] = $this->getCssUserColors();
		$markerArray['###CSS_USERCOLORS_COUNT###'] = $this->getCssUserColorsCount();		

		$markerArray['###COLORIZE_NICKS###'] = $this->conf['FLEX']['colorizeNicks'] ? "true" : "false";
		$markerArray['###SHOW_TIME###'] = $this->conf['FLEX']['showTime'] ? "true" : "false";		
		$markerArray['###ENABLE_SOUND###'] = ($this->conf['FLEX']['enableSound'] && isset($this->db->extCONF['soundSupport'])) ? "true" : "false";		
		$markerArray['###SHOW_EMOTICONS###'] = $this->conf['FLEX']['showEmoticons'] ? "true" : "false";
		$markerArray['###SHOW_STYLES###'] = $this->conf['FLEX']['showStyles'] ? "true" : "false";
		
		$markerArray['###POPUP_JS_WINDOW_PARAMS###'] = $this->conf['chatPopupJSWindowParams'];
		
		$markerArray['###USERLIST_PM_CONTENT###'] = $this->cObj->stdWrap($this->conf['userlistPMContent'], $this->conf['userlistPMContent.']);
		$markerArray['###USERLIST_PR_CONTENT###'] = $this->cObj->stdWrap($this->conf['userlistPRContent'], $this->conf['userlistPRContent.']);
		$markerArray['###USERLIST_PM_INFO###'] = t3lib_div::slashJS($this->pi_getLL('userlistPMInfo'));
		$markerArray['###USERLIST_PR_INFO###'] = t3lib_div::slashJS($this->pi_getLL('userlistPRInfo'));
		$markerArray['###TALK_TO_ROOM_NAME###'] = t3lib_div::slashJS($this->pi_getLL('talktoroomname'));
		$markerArray['###SNIPPETS_ERROR###'] = t3lib_div::slashJS($this->pi_getLL('snippetsError'));

		$markerArray['###ALLOW_PRIVATE_MESSAGES###'] = $this->db->extCONF['allowPrivateMessages'] ? "true" : "false";
		$markerArray['###ALLOW_PRIVATE_ROOMS###'] = $this->db->extCONF['allowPrivateRooms'] ? "true" : "false";		
		
		$markerArray['###USE_SNIPPETS###'] = $this->conf['useSnippets'] ? 'true' : 'false';
	
		$tooltipOffsetXY = t3lib_div::trimExplode(',', $this->conf['tooltipOffsetXY']);
		$markerArray['###TOOLTIP_OFFSET_X###'] = $tooltipOffsetXY[0];
		$markerArray['###TOOLTIP_OFFSET_Y###'] = $tooltipOffsetXY[1];

		$markerArray['###ISPOPUP###'] = $this->cObj->data['popup'] ? 'true' : 'false';
		
		$soundConf = $this->conf['soundSupport.'];
		
		$markerArray['###SOUND_SUPPORT###'] = $this->db->extCONF['soundSupport'];
		$markerArray['###SOUND_SUPPORT_OPTIONS###'] = 'null';
		$markerArray['###SOUND_MESSAGE###'] = 'null';
		$markerArray['###SOUND_USERLIST###'] = 'null';

		$markerArray['###SOUND_MESSAGE###'] = '"'.$this->cObj->stdWrap($soundConf['message'], $soundConf['message.']).'"';
		$markerArray['###SOUND_USERLIST###'] = '"'.$this->cObj->stdWrap($soundConf['userListChange'], $soundConf['userListChange.']).'"';

		
		$markerArray['###SOUND_SUPPORT_EXTRA###'] = '';
		if($this->db->extCONF['soundSupport'] == 'soundmanager2') {
			$markerArray['###SOUND_SUPPORT_OPTIONS###'] = '{}';
		}

		/*
		if($this->db->extCONF['soundSupport'] == 'jssoundkt') {
			$markerArray['###SOUND_SUPPORT_OPTIONS###'] = '{ swfLocation : "'.t3lib_extMgm::siteRelPath('jssoundkt').'jssoundkt-0.1/SoundBridge.swf" }';
		}
		*/

		if($this->db->extCONF['soundSupport'])
			$markerArray['###SOUND_SUPPORT_EXTRA###'] = $this->cObj->cObjGetSingle($soundConf['extraJS'], $soundConf['extraJS.']);
		
		$markerArray['###LOADING_MESSAGE###'] = $this->cObj->cObjGetSingle($this->conf['loadingMessage'], $this->conf['loadingMessage.']);

		$setup = $this->conf['chatbuttons_on.'];
		$chatbuttons_on = array();
		$chatbuttons_keys = array();
		foreach($setup as $key => $value)	{
			$theValue = $setup[$key];
			if (!strstr($key,'.'))	{
				$conf = $setup[$key.'.'];
				$chatbuttons_on[] = $this->cObj->cObjGetSingle($theValue,$conf,$key);	// Get the contentObject
				$chatbuttons_off[] = '';
				$chatbuttons_keys[] = str_replace('_','-',$key);
			}
		}
		
		$markerArray['###CHATBUTTONS_KEYS###'] = "Array('".implode("','", $chatbuttons_keys)."')";
		$markerArray['###CHATBUTTONS_ON###'] = "Array('".implode("','", $chatbuttons_on)."')";
		$markerArray['###CHATBUTTONS_OFF###'] = "Array('".implode("','", $chatbuttons_off)."')";
		
		$markerArray['###DEBUG_CONTAINER###'] = $this->debug ? $this->cObj->cObjGet($this->conf['debugTemplate.']) : '';
		
		$markerArray['###CURRENTMESSAGESTYLE###'] = $this->user['tx_vjchat_chatstyle'];

		
		// display CHATROOM
		if(!$this->cObj->data['isFull']) {

			if($this->conf['useSnippets']) {
					// try to add user
				$this->db->updateUserInRoom($room->uid, $this->user['uid'], tx_vjchat_lib::isSuperuser($room, $this->user), $this->pi_getLL('user_enters_chat'));
				$this->db->setUserlistSnippet($room->uid, $this->user['uid'], $this->getSnippet($room, $this->user, $this->conf['userlistSnippet.']));
				$this->db->setTooltipSnippet($room->uid, $this->user['uid'], $this->getSnippet($room, $this->user, $this->conf['tooltipSnippet.']));
			}
		
			$subpart = $this->cObj->getSubpart($template, '###CHATROOM###');

			$markerArray['###SUBMIT_MESSAGE###'] = $this->pi_getLL('submit_message');
			$markerArray['###LABEL_NEW_MESSAGE###'] = $this->pi_getLL('new_message');
			$markerArray['###REFRESH_MESSAGES_TIME###'] = $this->conf['FLEX']['refreshMessagesTime']*1000;	
			$markerArray['###REFRESH_USERLIST_TIME###'] = $this->conf['FLEX']['refreshUserListTime']*1000;				

			$subpart_TOOLS_CONTAINER = $this->cObj->getSubpart($subpart, '###TOOLS_CONTAINER###');		
	
			if(!$this->conf['FLEX']['showFormatting']) 
				$subpartMarkerArray_TOOLS_CONTAINER['###FORMAT_CONTAINER###'] = '';
			else {
				$subpart_FORMAT_CONTAINER = $this->cObj->getSubpart($subpart_TOOLS_CONTAINER, '###FORMAT_CONTAINER###');
				$this->cObj->data['enableEmoticons'] = $this->conf['FLEX']['showEmoticons'];
				$this->cObj->data['enableUsercolors'] = $this->conf['FLEX']['colorizeNicks'];
				$this->cObj->data['enableUserstyles'] = $this->conf['FLEX']['showStyles'];
				$this->cObj->data['enableTime'] = $this->conf['FLEX']['showTime'];
				$this->cObj->data['enableSound'] = $this->conf['FLEX']['enableSound'] && $this->db->extCONF['soundSupport'];
				$markerArray_FORMAT_CONTAINER['###CHATBUTTONS###'] =  $this->cObj->cObjGet($this->conf['chatbuttons.']);
				$subpartMarkerArray_TOOLS_CONTAINER['###FORMAT_CONTAINER###'] = $this->cObj->substituteMarkerArray($subpart_FORMAT_CONTAINER, $markerArray_FORMAT_CONTAINER);
			}
	
			$subpart_EMOTICONS = $this->cObj->getSubpart($subpart_TOOLS_CONTAINER, '###EMOTICONS_CONTAINER###');
			$markerArray_EMOTICONS['###EMOTICONS###'] = tx_vjchat_lib::getEmoticonsForChatRoom();
			$markerArray_EMOTICONS['###EMOTICONS_DISPLAY###'] = $this->conf['FLEX']['showEmoticons'] ? "block" : "none";
			$subpartMarkerArray_TOOLS_CONTAINER['###EMOTICONS_CONTAINER###'] = $this->cObj->substituteMarkerArray($subpart_EMOTICONS, $markerArray_EMOTICONS);

			$subpart_STYLES = $this->cObj->getSubpart($subpart_TOOLS_CONTAINER, '###STYLING_CONTAINER###');
			$markerArray_STYLES['###STYLES###'] = $this->getStylingContainer();
			$markerArray_STYLES['###STYLES_DISPLAY###'] = $this->conf['FLEX']['showStyles'] ? "block" : "none";				
			$subpartMarkerArray_TOOLS_CONTAINER['###STYLING_CONTAINER###'] = $this->cObj->substituteMarkerArray($subpart_STYLES, $markerArray_STYLES);

			$subpartMarkerArray['###TOOLS_CONTAINER###'] = '';
			if($this->conf['FLEX']['showFormatting'] || $this->conf['FLEX']['showEmoticons']) {
				$subpartMarkerArray['###TOOLS_CONTAINER###'] = $this->cObj->substituteMarkerArrayCached($subpart_TOOLS_CONTAINER, $markerArray_TOOLS_CONTAINER, $subpartMarkerArray_TOOLS_CONTAINER);
			}
			
			$subpart_SEND_BUTTON = $this->cObj->getSubpart($subpart, '###SEND_BUTTON###');
			if($this->conf['FLEX']['showSendButton']) {
				$markerArray_SEND_BUTTON['###LABEL_SUBMIT###'] = $this->pi_getLL('submit_message');
				$subpartMarkerArray['###SEND_BUTTON###'] = $this->cObj->substituteMarkerArray($subpart_SEND_BUTTON, $markerArray_SEND_BUTTON);
				
			}
			else
				$subpartMarkerArray['###SEND_BUTTON###'] = '';
		
		}
		// display CHATROOM_FULL
		else {
			$subpart = $this->cObj->getSubpart($template, '###CHATROOM_FULL###');

			$markerArray['###CHATROOM_ID###'] = $roomId;
			$markerArray['###SCRIPTURL###'] = $this->chatScript;
			$markerArray['###RELOAD_TIME###'] = $this->conf['FLEX']['reloadTimeIfRoomFull']*1000;
			$markerArray['###CHATURL###'] = $this->pi_linkTP_keepPIvars_url(array(), 0, false);
			$markerArray['###USERID###'] = $this->user['uid'];			
		}
		
		$theValue = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartMarkerArray);		
	
		// prepend the subpart COMMON
		$common = $this->cObj->getSubpart($template, '###COMMON###');
		$common = $this->cObj->substituteMarkerArray($common, $markerArray);		

		$theValue = $common.$theValue;
		//t3lib_div::debug($this->cObj->data);
		return $this->cObj->stdWrap($theValue, $this->conf['views.']['chat.']['stdWrap.']);
	

	}
	
	/*
	function getChatConfiguration($room) {
		
		$configuration = array(
		
			'roomId' 		=> $room->uid,
			'userId' 		=> $this->user['uid'],
			'scriptUrl' 	=> $this->chatScript,
			'leaveUrl' 		=> $this->pi_linkTP_keepPIvars_url(array(), 0, true),
			'newWindowUrl' 	=> $this->getNewWindowUrl(),
			'initialId' 	=> $this->db->getLatestEntryId($room, $time),
			'charset' 		=> $GLOBALS['TSFE']->renderCharset,
			'debugMode' 	=> $this->debug,	
			'lang' 			=> $GLOBALS['TSFE']->config['config']['language'],		

			
		usernameGlue : "###USERNAMESGLUE###",
		usernamesFieldGlue : "###USERNAMESFIELDGLUE###",
		messagesGlue : "###MESSAGESGLUE###",
		idGlue : "###IDGLUE###",
		chatUserNameFieldSuperusers : "###CHAT_USERNAME_FIELD_SUPERUSERS###",
		chatUserNameFieldExperts : "###CHAT_USERNAME_FIELD_EXPERTS###",
		chatUserNameFieldModerators : "###CHAT_USERNAME_FIELD_MODERATORS###",
		chatUserNameFieldUsers : "###CHAT_USERNAME_FIELD_USERS###",
		userColors : ###USERCOLORS###,
		colorizeNicks : ###COLORIZE_NICKS###,
		showTime : ###SHOW_TIME###,
		showEmoticons : ###SHOW_EMOTICONS###,
		showStyles : ###SHOW_STYLES###,
		refreshMessagesTime	: ###REFRESH_MESSAGES_TIME###,				
		refreshUserlistTime	: ###REFRESH_USERLIST_TIME###,					
		popup : ###ISPOPUP###,
		popupJSWindowParams : "###POPUP_JS_WINDOW_PARAMS###",
		userlistPMContent :	'###USERLIST_PM_CONTENT###',
		userlistPRContent : '###USERLIST_PR_CONTENT###',
		userlistPMInfo : '###USERLIST_PM_INFO###',
		userlistPRInfo : '###USERLIST_PR_INFO###',
		talkToNewRoomName : "###TALK_TO_ROOM_NAME###",
		allowPrivateMessages : ###ALLOW_PRIVATE_MESSAGES###,
		allowPrivateRooms : ###ALLOW_PRIVATE_ROOMS###,
		globalInstanceName : "chat_instance",
		useSnippets : ###USE_SNIPPETS###,
		snippetsError : '###SNIPPETS_ERROR###',
		tooltipOffsetX : ###TOOLTIP_OFFSET_X###,
		tooltipOffsetY : ###TOOLTIP_OFFSET_Y###,
		focusOnNewMessage : false,
		chatbuttonson : ###CHATBUTTONS_ON###,
		chatbuttonsoff : ###CHATBUTTONS_OFF###,
		chatbuttonskeys : ###CHATBUTTONS_KEYS###,
		enableSound : ###ENABLE_SOUND###,
		soundSupportOptions : ###SOUND_SUPPORT_OPTIONS###,
		soundSupportName : "###SOUND_SUPPORT###",
		soundMessage : ###SOUND_MESSAGE###,
		soundUserlist : ###SOUND_USERLIST###,
		chatWindow : self,
		JSdebug : false		
	}
	*/

	function displaySessionsOfRoom($roomId) {

		if(!$room = $this->db->getRoom($roomId))
			return	$this->displayErrorMessage($this->pi_getLL('error_room_not_found'), $this->conf['views.']['sessions.']['stdWrap.']);

		//check rights to view room
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return  $this->displayErrorMessage($this->pi_getLL('error_room_access_denied'), $this->conf['views.']['sessions.']['stdWrap.']);

		if(!$sessions = $this->db->getSessionsOfRoom($roomId))
			return $this->displayErrorMessage($this->pi_getLL('sessions_not_found'), $this->conf['views.']['sessions.']['stdWrap.']);
		
		foreach($sessions as $session) {

			$this->cObj->data = $this->getSessionData($session);
			$this->cObj->data['entriesCount'] = $this->db->getEntriesCountOfSession($session);

			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.']['sessions.']['oneSession.']);
		}
		
		$this->cObj->data = t3lib_div::array_merge($this->cObj->data, $this->prefixAssocArrayKeys('room.', $room->toArray()));
	
		return $this->cObj->stdWrap($theValue, $this->conf['views.']['sessions.']['stdWrap.']);
		
	}

	function prefixAssocArrayKeys($prefix, $array) {
		$theValue = array();
		foreach($array as $key => $value) {
			$theValue[$prefix.$key] = $value;
		}
		return $theValue;
	}
	
	function displayErrorMessage($message, $stdWrap = "") {
		$theValue = $this->cObj->stdWrap($message, $this->conf['errorMessagesStdWrap.']);
		return $this->cObj->stdWrap($theValue, $stdWrap);
	}
	
	function displaySession($sessionId) {

		if(!$session = $this->db->getSession($sessionId))
			return $this->displayErrorMessage($this->pi_getLL('session_not_found'), $this->conf['views.']['session.']['stdWrap.']);		
		
		if(!$room = $this->db->getRoom($session->room))
			return $this->displayErrorMessage($this->pi_getLL('room_not_found'), $this->conf['views.']['session.']['stdWrap.']);

		$this->db->cleanUpUserInRoom($room->uid, 10, true, $this->pi_getLL('user_leaves_chat'));

		//check rights to view room
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));
			
		
		$entries = $this->db->getEntriesOfSession($session);

		$isModerator = tx_vjchat_lib::isModerator($room, $this->user['uid']);
	
		foreach($entries as $entry) {
			$this->cObj->data = $entry->toArray();
			$this->cObj->data['isModerator'] = $isModerator;

			$feuser = $this->db->getFeUser($entry->feuser);
			if($feuser['username'])
				$this->cObj->data['username'] = tx_vjchat_lib::getChatUserName($room, $feuser['name']);
			else
				$this->cObj->data['username'] = 'SYSTEM';
			
			$this->cObj->data['type'] = 0;
			if(tx_vjchat_lib::isModerator($room, $entry->feuser))
				$this->cObj->data['type'] = 1;

			if(tx_vjchat_lib::isSystem($entry->feuser))
				$this->cObj->data['type'] = 2;

			if(tx_vjchat_lib::isExpert($room, $entry->feuser))
				$this->cObj->data['type'] = 3;
		
			$this->cObj->data['entry'] = tx_vjchat_lib::formatMessage($entry->entry);

			// render COBJ from TS with current data
			$theValue .= $this->cObj->cObjGet($this->conf['views.']['session.']['oneEntry.']);
		}
		
		$this->cObj->data = $this->getSessionData($session);
		$this->cObj->data['entriesCount'] = count($entries);


		$this->cObj->data = t3lib_div::array_merge($this->cObj->data, $this->prefixAssocArrayKeys('room.', $room->toArray()));
		
		return $this->cObj->stdWrap($theValue, $this->conf['views.']['session.']['stdWrap.']);
		
	}
	
	function displayOverallChatuserNumber() {
	
		$rooms = $this->getRoomsFromFlexConf();
	
		$roomIds = array();
		foreach($rooms as $room) {
			$roomIds[] = $room->uid;
		}

		$this->cObj->data['overallChatUserCount'] = $this->db->getUserCountOfRoom($roomIds);
		$this->cObj->data['targetpid'] = $this->conf['FLEX']['targetwindow'];
		return $this->cObj->cObjGet($this->conf['views.']['overallChatUserCount.']);
	}
	
	function deleteEntry($entryId) {
		// check rights
		$entry = $this->db->getEntry($entryId);
		
		$room = $this->db->getRoom($entry->room);
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));
		
		if(!tx_vjchat_lib::isModerator($room, $this->user['uid']))
			return $this->displayErrorMessage($this->pi_getLL('access_denied'));
		
		return $this->db->deleteEntry($entryId);
	}
	
	function getRoomData($room) {
		$theValue = $room->toArray();
		
		$theValue['userCount'] = $this->db->getUserCountOfRoom($room->uid);
		$theValue['showUserCount'] = $this->conf['FLEX']['maxUserCount'];
		$theValue['sessionCount'] = $this->db->getSessionsCountOfRoom($room->uid);
		$theValue['isFull'] = $this->db->isRoomFull($room) && !tx_vjchat_lib::isSuperuser($room, $this->user) && !$this->db->isMemberOfRoom($room->uid, $this->user['uid']);

		$conf = $this->conf['views.'][$this->conf['tsRooms'].'.'];

		$superusers = $experts = $moderators = $users = array();
		if($this->conf['FLEX']['showSuperusers']) {
			$superusers = $this->db->getOnlineSuperusers($room->uid);
			$this->cObj->data['userType'] = 'superuser';
			$theValue['onlineSuperuser'] = tx_vjchat_lib::getUsernames($superusers, $room->chatUserNameFieldSuperusers, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

		if($this->conf['FLEX']['showExperts']) {		
			$experts = $this->db->getOnlineExperts($room->uid);
			$this->cObj->data['userType'] = 'expert';
			$theValue['onlineExperts'] = tx_vjchat_lib::getUsernames($experts, $room->chatUserNameFieldExperts, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

		if($this->conf['FLEX']['showModerators']) {
			$moderators = $this->db->getOnlineModerators($room->uid);
			$this->cObj->data['userType'] = 'moderator';
			$theValue['onlineModerators'] = tx_vjchat_lib::getUsernames($moderators, $room->chatUserNameFieldModerators, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}

		if($this->conf['FLEX']['showUsers']) {
			$users = $this->db->getOnlineUsers($room->uid);
			$this->cObj->data['userType'] = 'user';
			$theValue['onlineUsers'] = tx_vjchat_lib::getUsernames($users, $room->chatUserNameFieldUsers, $conf['usersGlue'], $this->cObj, $conf['users_stdWrap.']);
		}
		
		$allUsers = $this->db->getOnlineUsers($room->uid);
		
		$snippets = array();
		$usernicknames = array();
		foreach($allUsers as $user) {
			$userSnippets = $this->db->getSnippets($room->uid, $user['uid']);
			$this->cObj->data = array_merge($this->cObj->data, $user);
			$singleSnippet = $this->cObj->stdWrap($userSnippets['userlistsnippet'], $conf['users_stdWrap.']);
			$snippets[] = $singleSnippet;
			$usernicknames[] = tx_vjchat_lib::getChatUserName($room, $user);
		}
		$theValue['allUserNicknames'] = implode(', ', $usernicknames);

		$theValue['allUserSnippets'] = implode($conf['usersGlue'], $snippets);
	
		if(!$theValue['isFull'] && !$room->isClosed() && !$this->piVars['popup'])
			$theValue['chatwindow'] = $this->conf['FLEX']['chatwindow'];
		else
			$theValue['chatwindow'] = false;
		
		$theValue['newWindowUrl'] = $this->getNewWindowUrl();

		$theValue['popup'] = $this->piVars['popup'];
		$theValue['leaveChat'] = ($this->conf['FLEX']['display'] == 'rooms') && ($theValue['popup'] == false);

		return $theValue;
	}
	
	function getNewWindowUrl() {
		if($this->conf['FLEX']['chatwindow'])
			$theValue = $this->conf['FLEX']['chatwindow'] ? $this->pi_linkTP_keepPIvars_url(array(), 0, true, $this->conf['FLEX']['chatwindow']) : $this->pi_linkTP_keepPIvars_url(array(), 0, true);		
		else
			$theValue = ($this->pi_linkTP_keepPIvars_url(array(), 0, true)).'&type='.($this->conf['chatwindow.']['typeNum']);
		return t3lib_div::getIndpEnv('TYPO3_SITE_URL').$theValue;
	}
	
	function getSessionData($session) {
		$theValue = $session->toArray();
		$entryStart = $this->db->getEntry($session->startid);
		$entryEnd = $this->db->getEntry($session->endid);
		$theValue['startdate'] = $entryStart->tstamp;
		$theValue['enddate'] = $entryEnd->tstamp;
		
		return $theValue;
	}
	
	function getStylingContainer() {
		
		// it must be defined at least two styles (default + 1)
		if(!$this->conf['messageStyles.']['1'])
			return "";

		$out = "";
		
		$this->conf['messageStyles.']['0'] = $this->conf['messageStyles.']['default'];
		$this->conf['messageStyles.']['0.'] = $this->conf['messageStyles.']['default.'];		
		
		$i = 0;
		while($this->conf['messageStyles.'][$i]) {
		
			$this->cObj->data['number'] = $i;
			$out .= $this->cObj->cObjGetSingle($this->conf['messageStyles.'][$i], $this->conf['messageStyles.'][$i.'.']);
			$i++;
		}
		
		return $out;
		
	}
	
	/**
	  * Generates a javascript array with colors for the users
	  */
	function getUserColorArray() {
	
		$array = t3lib_div::trimExplode(',',$this->conf['userColors']);

		for($i = 0; $i<count($array); $i++) {
		
			$out = $out.'\''.$array[$i].'\'';
	
			if($i<count($array)-1)
				$out = $out.',';
		}
		
		return ' Array('.$out.')';
		
	}

	function getCssUserColorsCount() {

		$array = t3lib_div::trimExplode(',',$this->conf['userColors']);
		return count($array);
		
	}

	
	function getCssUserColors() {

		$array = t3lib_div::trimExplode(',',$this->conf['userColors']);

		for($i = 0; $i<count($array); $i++) {
		
			$out = $out." .usercolor-$i { color: ".$array[$i]."; } ";

			if($i<count($array)-1)
				$out = $out.chr(10);
		}

		$out = '<style type="text/css">'.chr(10).$out.chr(10).'</style>';
		
		return $out;

		
	}
	

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_pi1.php']);
}

?>