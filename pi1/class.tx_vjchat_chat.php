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
 
require_once('class.tx_vjchat_room.php');
require_once('class.tx_vjchat_entry.php');

require_once('class.tx_vjchat_db.php');
require_once('class.tx_vjchat_lib.php');

//require_once(PATH_t3lib."class.t3lib_div.php");
require_once(PATH_site.'typo3/sysext/lang/lang.php');


class tx_vjchat_chat {

	var $lang;
	var $commands;
	var $db;
	
	var $env;
	var $debug = false;
	var $debugMessages = array();
	var $timer = null;
	
	var $room;
	var $user;
	
	var $lastMessageId;
	
	var $extConf;

	function init($user, $charset, $timer) {
		// load language files
		// at this moment it is impossible to modify this via TypoScript
		//$LLKey = $GLOBALS['TSFE']->config['config']['language'];
		$this->microtime = microtime();	
		$this->timer = $timer;

		$this->extConf = tx_vjchat_lib::getExtConf();
		
		// get parameters
		$this->env['user'] = $user->user;
		$this->env['room_id'] = intval(t3lib_div::_GP('r'));
		$this->env['pid'] = intval(t3lib_div::_GP('p'));
		$this->env['charset'] = $charset;		
		
		// decode message
		$this->env['msg'] = base64_decode(t3lib_div::_GP('m'));
		//$this->env['msg'] = tx_vjchat_lib::utf8RawUrlDecode($this->env['msg'], $this->env['charset'] == 'utf-8');
		$this->env['msg'] = utf8_decode($this->env['msg']);
		$this->env['msg'] = str_replace('<', '&lt;', $this->env['msg']);		
		$this->env['msg'] = str_replace('>', '&gt;', $this->env['msg']);

		$this->env['action'] = htmlspecialchars(t3lib_div::_GP('a'));
		$this->env['lastid'] = intval(t3lib_div::_GP('t'));
		$this->env['ulhash'] = tx_vjchat_lib::utf8RawUrlDecode(t3lib_div::_GP('ulhash'), $this->env['charset'] == 'utf-8');		
		$this->env['uid'] = intval(t3lib_div::_GP('uid'));
		$this->env['usercolor'] = intval(t3lib_div::_GP('uc'));
		$this->env['LLKey'] = htmlspecialchars(t3lib_div::_GP('l'));		

		$this->lang = t3lib_div::makeInstance('language');
		$this->lang->init($this->env['LLKey']);
		$this->lang->includeLLFile("EXT:vjchat/pi1/locallang.php");

		$this->db = t3lib_div::makeInstance('tx_vjchat_db');
		$this->db->lang = $this->lang;
		$this->db->setDebug(true);
		
		$this->room = $this->db->getRoom($this->env['room_id']);
		$this->user = $this->env['user'];
		$this->feUsersOfRoom = null;
		
		if(t3lib_div::_GP('d') == 'true') 
			$this->debug = true;

		$this->debugMessage('init');

		$this->lastMessageId = $this->env['lastid'];

		// init commands
		$this->initCommands();
		
	}
	
	function getMicrotimeAsFloat($microtime = NULL) {
		if(!$microtime)
			$microtime = microtime();
	
		list($usec, $sec) = explode(" ", $microtime);
		return ((float)$usec + (float)$sec);
	}

	/**********************************************************************************************/
	// FUNCTION CALLED BY CLIENT JAVASCRIPT
	/**********************************************************************************************/	

	function perform() {
		switch ($this->env['action']) {
				// check if room is full
			case 'checkfull': 
				return $this->checkFull();
				// get messages
			case 'gm':
				return $this->getMessages($this->env['lastid']);
			break;
				// send message
			case 'sm':
				return $this->putMessage($this->env['msg'],$this->env['lastid']);
			break;	
				// get userlist
			case 'gu':
				return $this->getUserlist();
			break;
				// unhide message
			case 'commit':
				return $this->commitMessage($this->env['uid']);
			break;
		}
				
	}

	function getMicrotime() {
		$result = $this->getMicrotimeAsFloat() - $this->getMicrotimeAsFloat($this->microtime);
		$this->microtime = microtime();
		return $result;
	}

	function debugMessage($message) {
		if($this->debug)
			$this->debugMessages[] = $message;
	}

	/**
	  * Prepares an array of messages for client. This means prepending each message with [MSG] and adding a timestamp after [TIME]
	  * @param messages
	  * @param container name for result messsages
	  * @return string
	  */
	function returnMessage($messages, $messagescontainer = 'messages') {

		if(!$this->debug && $messages == null)
			return;
	
		if($messages != null) {
			if(!is_array($messages))
				$messages = array($messages);
		}
		
			// client expects utf8 encoded messages
		foreach($messages as $key => $message)
			$messages[$key] = utf8_encode($messages[$key]);
		
		$return = array('id' => $this->lastMessageId, $messagescontainer => $messages, 'debug' => $this->debugMessages, 'timers' => $this->timer->output());
		
		$json = tx_vjchat_lib::json_encode($return, 'chatresponse');
		$base64 = base64_encode($json);
		return $base64;
		
	}
	
	function returnUserlist($users, $messagescontainer = 'useritems') {
	
		$ulhash = '';
	
		if($users != null) {
		
			if(!is_array($users))
				$users = array($users);
		
			$hashBase = array();
			foreach($users as $user) {
				$hashBase[] = $user['uid'];
			}
				// ulhash prevents sending userlist to client if there aren't any updates
			$ulhash = t3lib_div::shortMD5(implode($hashBase));
			if(strcmp($ulhash,$this->env['ulhash']) == 0) {
				$users = null;
			}
		}

		

		if(!$this->debug && ($users == null || strcmp($ulhash,$this->env['ulhash']) == 0))
			return;		
		
			// client expects utf8 encoded messages
		//foreach($users as $key => $user)
			//$users[$key]['username'] = utf8_encode($users[$key]['username']);
			
		$callback =  array($this, 'utf8_encode_array');
		$this->array_walk_recursive($users, $callback);
		
		$return = array('ulhash' => $ulhash, $messagescontainer => $users, 'debug' => $this->debugMessages, 'timers' => $this->timer->output());
		
		$json = tx_vjchat_lib::json_encode($return, 'chatresponse');
		$base64 = base64_encode($json);
		return $base64;
		
	}
	
	function putMessage($msg, $lastid, $tofeuserid = 0) {

		$this->debugMessage(__LINE__.'putMessage: '.$msg);
	
		if($msg == '')
			return;
	
		if(!tx_vjchat_lib::isSuperuser($this->room, $this->user)) {
	
			// check if user is allowed to put message into this room
			if(!tx_vjchat_lib::checkAccessToRoom($this->room, $this->user))
				return $this->returnMessage('<span class="tx-vjchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>');
		
			// check if user is kicked
			if($res = $this->db->isUserKicked($this->room->uid, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.sprintf($this->lang->getLL('error_kicked'),$res).'</span>', '/quit'));
	
			// check if user is banned
			if(tx_vjchat_lib::isBanned($this->room, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.$this->lang->getLL('error_banned').'</span>', '/quit'));

		}
		
		// check for commands
		// if it is a command (indicated with first char '/' perform and return result )
		if($msg[0] == '/') {
			return $this->performCommand(trim($msg));
		}

		// just put message if it is a normal chat room
		// or the user is a moderator or expert
		// if it is private message ($tofeuserid != null) send a hidden message
		if(!$this->room->isExpertMode() || tx_vjchat_lib::isModerator($this->room, $this->user['uid'])  || tx_vjchat_lib::isExpert($this->room, $this->user['uid'])) {
			$this->db->putMessage($this->room->uid, $msg, $this->user['tx_vjchat_chatstyle'], $this->user, ($tofeuserid ? true : false), $this->user['uid'], $tofeuserid);
			return $this->getMessages($lastid);
		}
		
		// otherwise put a hidden message
		$this->db->putMessage($this->room->uid, $msg, $this->user['tx_vjchat_chatstyle'], $this->user, true, $this->user['uid'], $tofeuserid);	
		return $this->getMessages($lastid);
	
	}
	
	function checkFull() {
		return ($this->db->isRoomFull($this->room) && !$this->db->isMemberOfRoom($this->room->uid, $this->user['uid'])) ? 'full' : 'notfull';
	}
	
	function getMessages($lastid) {

		$this->debugMessage('getMessage');
	
		$this->db->setDebug(true);

		if(!tx_vjchat_lib::isSuperuser($this->room, $this->user)) {
		
			// check if user is banned
			if(tx_vjchat_lib::isBanned($this->room, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.$this->lang->getLL('error_banned').'</span>', '/quit'));
	
			// check if user is kicked
			if($res = $this->db->isUserKicked($this->room->uid, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.sprintf($this->lang->getLL('error_kicked'),$res).'</span>', '/quit'));
	
			// check if this is a private room and if the user is an invited member
			if($this->room->private && !tx_vjchat_lib::isMember($this->room, $this->user['uid']))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.$this->lang->getLL('error_not_invited').'</span>', '/quit'));
			
			// remove user who left room and remove system messages
			$this->db->cleanUpUserInRoom($this->room->uid, 20, true, $this->lang->getLL('user_leaves_chat'));
		
			// check if user is allowed to put a message into this room
			if(!tx_vjchat_lib::checkAccessToRoom($this->room, $this->user))
				return $this->returnMessage(array('<span class="tx-vjchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>','/quit'));

		}

		// updateUserData
		// if user not already in room try to add
		$resUpdate = $this->db->updateUserInRoom($this->room->uid, $this->user['uid'], tx_vjchat_lib::isSuperuser($this->room, $this->user), $this->lang->getLL('user_enters_chat'));
	
		// quit here if room is full
		if($resUpdate === "full") 
			return $this->returnMessage('full');
			
		$entries = $this->db->getEntries($this->room, $lastid);
		
		if(count($entries) == 0)
			return $this->returnMessage(null);
		
		$messages = array();
		foreach($entries as $entry) {
	
			$this->debugMessage('entry: '.($entry->entry));
	
			// if message is a quit message for current client 
			if((preg_match('/^\/quit/i', $entry->entry)) && ($this->user['uid'] == $entry->feuser)) {
				$this->debugMessage('/quit called');
				$this->db->leaveRoom($this->room->uid, $entry->feuser);
				$this->db->deleteEntry($entry->uid);
				return $this->returnMessage('/quit');		// will be handled by client javascript
			}

			// delete from db if entry is a command and continue with next entry
			if(preg_match('/^\//i', $entry->entry)) {
				$this->db->deleteEntry($entry->uid);
				continue;
			}

			// first check if this entry should be sent to client
			// a) expert mode
			// - sent if message is not hidden
			// - if it is hidden only sent to moderators client
			// b) normal mode
			// - sent message without checking anything
			// c) private message
			// - sent message only to dest user
			// d) a superuser should receive all messages
			if(!$entry->isPrivate()) {
				if($this->room->isExpertMode() && $entry->hidden) {
					if(!tx_vjchat_lib::isSuperuser($this->room, $this->user) && !tx_vjchat_lib::isModerator($this->room, $this->user['uid']) && ($this->user['uid'] != $entry->feuser))
						continue;	// skip to next entry
				}
			}
			else {
			
				$this->debugMessage('PM from '.$entry->feuser.' to '.($entry->tofeuserid));
			
				$involved = ($entry->tofeuserid == $this->user['uid']) || ($entry->feuser == $this->user['uid']);
			
				// if this is a private message check if this message should be received by the current user
				// if superuser skip message if he is not allowed to view private messages
				if(tx_vjchat_lib::isSuperuser($this->room, $this->user) && !$this->extConf['superuserCanReadPMs'] && !$involved) 
					continue;
					
				// if not a superuser check show message to sender an recipient only
				if(!tx_vjchat_lib::isSuperuser($this->room, $this->user) && !$involved)
					continue;	// skip to next entry
					
				$this->debugMessage('PM user involved');
				
			}
	
			// get User of entry
			// if this entry was sent by system we cannot get a FeUser
			// so we have to assign the username SYSTEM
			$entryUser = NULL;
			if(tx_vjchat_lib::isSystem($entry->feuser))
				$username = $this->lang->getLL('system_name');
			else {
				$entryUser = $this->db->getFeUser($entry->feuser);	// this holds the complete user array
				$username = tx_vjchat_lib::getChatUserName($this->room, $entryUser);
			}
			
			$this->debugMessage(__LINE__);
			
			// the superuser should know the recipient of a private message
			//if(tx_vjchat_lib::isSuperuser($this->room, $this->user) && $entry->isPrivate()) {
			if($entry->isPrivate()) {
				$recipient = $this->db->getFeUser($entry->tofeuserid);
				$username = sprintf($this->lang->getLL('privateMsgUsernamens'), $username, tx_vjchat_lib::getChatUserName($this->room, $recipient));
			}
			
			$this->debugMessage(__LINE__);
			
			$entryText = tx_vjchat_lib::formatMessage($entry->entry, $this->extConf['emoticonsPath']);

			$id = "";
			if(tx_vjchat_lib::isModerator($this->room, $this->user['uid']))
				$id = '#'.$entry->uid.'&nbsp;';

			$time = $entry->crdate;
			$time = strtotime($this->extConf['serverTimeOffset'], $time);

			// prepare message that should be sent to client
			$message = '<span class="tx-vjchat-time">'.strftime("%H:%M:%S", $time).':&nbsp;'.$id.'</span><span class="tx-vjchat-user tx-vjchat-userid-'.$entry->feuser.'">'.$username.'</span>&gt;&nbsp;<span class="tx-vjchat-entry">'.$entryText.'</span>';
	
			$this->debugMessage(__LINE__);	
	
			// if entry is hidden and user is a moderator then add a commit link
			if($entry->hidden) {
				$message = '<div class="tx-vjchat-hidden" id="tx-vjchat-entry-'.$entry->uid.'">'.$message.'</div>';
				if(tx_vjchat_lib::isModerator($this->room, $this->user['uid']) && !$entry->isPrivate())
					$message = $message.'<div class="tx-vjchat-commit" id="tx-vjchat-entry-commitlink-'.$entry->uid.'"><a class="tx-vjchat-actionlink" onClick="javascript:chat_instance.commitEntry('.$entry->uid.');">'.$this->lang->getLL('commit_message').'</a> | <a class="tx-vjchat-actionlink" onClick="javascript:chat_instance.hideEntry('.$entry->uid.');">'.$this->lang->getLL('hide_message').'</a> <span id="tx-vjchat-storelink-'.$entry->uid.'">| <a class="tx-vjchat-actionlink" onClick="javascript:chat_instance.storeEntry('.$entry->uid.');">'.$this->lang->getLL('store_message').'</a></span></div>';

				if($entry->isPrivate()) {
					$message = '<div class="tx-vjchat-private">'.$message.'</div>';
				}
			}

			$this->debugMessage(__LINE__);

			if($entryUser)
				$message = '<div class="tx-vjchat-'.tx_vjchat_lib::getUserTypeString($this->room, $entryUser).'">'.$message.'</div>';
			else
				$message = '<div class="tx-vjchat-system">'.$message.'</div>';
			
			$this->lastMessageId = $entry->uid;

			$groupstyles = $this->getUserGroupStyles($entryUser);

			$mid = t3lib_div::shortMD5(($entry->tstamp).($entry->uid));
			$message = '<div id="'.$mid.'" class="tx-vjchat-message-style-'.($entry->style).$groupstyles.'">'.$message.'</div>';
			
			$messages[] = $message;
			
			$this->debugMessage(__LINE__);
	
		}

		// if just entered chat
		if($resUpdate === "entered") {
			// welcome message
			$messages[] = $this->room->welcomemessage;
			$messages[] = $this->lang->getLL('after_welcome_message');
		}
		
		$this->debugMessage(__LINE__);
		$this->debugMessage(implode('\n',$messages));
	
		return $this->returnMessage($messages);
	
	}
	
	function getUserGroupStyles($user) {
		$groupsOfUser = t3lib_div::intExplode(',', $user['usergroup']);

		if(!is_array($groupsOfUser) || !count($groupsOfUser))
			return '';
			
		return ' tx-vjchat-usergroup-'.implode(' tx-vjchat-usergroup-',$groupsOfUser);
	}
	
	function getUserlist($room = NULL, $roomlistMode = false) {
	
		if(!$room)
			$room = $this->room;
	
		// check if user is allowed to put message in this room
		if(!tx_vjchat_lib::checkAccessToRoom($room, $this->user))
			return $this->returnMessage($this->lang->getLL('error_room_access_denied'));
	
		//$messages = $this->getUserNamesOfRoom($room);
		$users = $this->getUserlistOfRoom($room, $roomlistMode);
		return $this->returnUserlist($users, 'useritems');
	}	
	
	function commitMessage($entryId) {
		
		if(!tx_vjchat_lib::isModerator($this->room, $this->user['uid']))
			return $this->returnMessage('<span class="tx-vjchat-error">'.$this->lang->getLL('error_room_access_denied').'</span>');
			
		if($this->db->commitMessage($entryId))
			return $this->returnMessage('<span class="tx-vjchat-ok">'.sprintf($this->lang->getLL('message_committed'),$entryId).'</span>');
		else
			return $this->returnMessage('<span class="tx-vjchat-error">'.$this->lang->getLL('error_commit').'</span>');		
	}
	
	/**********************************************************************************************/
	// COMMANDS
	/**********************************************************************************************/	
	
	function initCommands() {
		$this->commands = array(
			'help' => array(
				'callback' => '_help',
				'description' => $this->lang->getLL('command_help'),
				'rights' => '1111',
			),

//>> Begin, Ergänzungen Udo Gerhards
			'smilies' => array(
				'callback' => '_smilies',
				'description' => $this->lang->getLL('command_smileys'),
				'rights' => '1111',
			),
			'roomlist' => array(
				'callback' => '_roomlist',
				'description' => $this->lang->getLL('command_roomlist'),
				'rights' => '1111',
				),
// >> End, Ergänzungen Udo Gerhards
			
			'who' => array(
				'callback' => '_who',
				'description' => $this->lang->getLL('command_who'),
				'rights' => '1111',
			),
			'whois' => array(
				'callback' => '_whois',
				'description' => $this->lang->getLL('command_whois'),
				'parameters' => array(
					'userId' => array(
						'regExp' =>'/(#([0-9]*)|[a-z0-9])?/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 0,
					),
				),
				'rights' => '1111',
			),
			'msg' => array(
				'callback' => '_msg',
				'hidefeedback' => '1',
				'description' => $this->lang->getLL('command_msg'),
				'parameters' => array(
					'userId' => array(
						'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
					'message' => array(
						'description' => $this->lang->getLL('command_param_message'),
						'required' => 1,
					),
				),
				'rights' => $this->extConf['allowPrivateMessages'] ? '1111' : '0001',
			),
			'kick' => array(
				'callback' => '_kick',
				'description' => $this->lang->getLL('command_kick'),				
				'parameters' => array(
					'userId' => array(
						'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
					'time' => array(
						'regExp' =>'/[0-9]*/',
						'description' => $this->lang->getLL('command_kick_param_time'),
						'required' => 0,
						'default' => 20,
					),
					'reason' => array(
						'description' => $this->lang->getLL('command_param_reason'),
						'required' => 0,
					),
				),
				'rights' => '0011',
			),
			'ban' => array(
				'callback' => '_ban',
				'description' => $this->lang->getLL('command_ban'),
				'parameters' => array(
					'userId' => array(
						'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
					'reason' => array(
						'description' => $this->lang->getLL('command_param_reason'),
						'required' => 0,
					),
				),
				'rights' => '0011',
			),
			'redeem' => array(
				'callback' => '_redeem',
				'description' => $this->lang->getLL('command_redeem'),
				'parameters' => array(
					'userId' => array(
						'regExp' =>'/(#([0-9]*)|[alphanum])?/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
					'reason' => array(
						'description' => $this->lang->getLL('command_param_reason'),
						'required' => 0,
					),
				),
				'rights' => '0011',
			),
			'quit' => array(
				'callback' => '_quit',
				'description' => $this->lang->getLL('command_quit'),
				'parameters' => array(
					'msg' => array(
						'description' => $this->lang->getLL('command_param_reason'),
						'required' => 0,
					),
				),
				'rights' => '1111',
			),
			'makesession' => array(
				'callback' => '_makesession',
				'description' => $this->lang->getLL('command_makesession'),
				'parameters' => array(
					'firstId' => array(
						'regExp' => '/^[0-9]*$/',
						'description' => $this->lang->getLL('command_makesession_param_firstid'),
						'required' => 1,
					),
					'lastId' => array(
						'regExp' => '/^[0-9]*$/',
						'description' => $this->lang->getLL('command_makesession_param_lastid'),
						'required' => 1,
					),
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_makesession_param_name'),
						'required' => 1,
					),
				),
				'rights' => $this->extConf['createSessions'] ? '0011' : '0000',
			),
			'makeexpert' => array(
				'callback' => '_makeexpert',
				'description' => $this->lang->getLL('command_makeexpert'),
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
				),
				'rights' => ($this->room && $this->room->isExpertMode()) ? '0011' : '0000',
			),
			'makeuser' => array(
				'callback' => '_makeuser',
				'description' => $this->lang->getLL('command_makeuser'),
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 1,
					),
				),
				'rights' => ($this->room && $this->room->isExpertMode()) ? '0011' : '0000',
			),
			'cleanup' => array(
				'callback' => '_cleanuproom',
				'description' => $this->lang->getLL('command_cleanup'),
				'rights' => $this->extConf['createSessions'] ? '0011' : '0000',
			),
			'cleanupall' => array(
				'callback' => '_cleanupall',
				'description' => $this->lang->getLL('command_cleanupall'),
				'rights' => '0001',
			),
			'switch' => array(
				'callback' => '_togglestatus',
				'description' => $this->lang->getLL('command_setstatus'),
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 0,
					),
					'status' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_status'),
						'required' => 1,
					),
				),
				'rights' => '0011',
			),
			'setstyle'	=> array(
				'callback' => '_setmessagestyle',
				'hideinhelp' => '1',
				'hidefeedback' => '1',
				'parameters' => array(
					'number' => array(
						'regExp' =>'/([0-9]*)/',
						'required' => 1,
					),
				),
				'rights' => '1111',
			),
			'newroom' => array(
				'callback' => '_newroom',
				'description' => $this->lang->getLL('command_newroom'),				
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_newroom_param_name'),
						'required' => 0,
					),
				),
				'rights' => $this->extConf['allowPrivateRooms'] ? '1111' : '0001',
			),
			'invite' => array(
				'callback' => '_invite',
				'description' => $this->lang->getLL('command_invite'),				
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 0,
					),
				),
				'rights' => ($this->room && ($this->room->private && tx_vjchat_lib::isOwner($this->room, $this->user['uid'])) ? '1111' : '0000'),
			),
			'recentinvite' => array(
				'callback' => '_recentinvite',
				'description' => $this->lang->getLL('command_recentinvite'),
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_userid'),
						'required' => 0,
					),
				),
				'hideinhelp' => '1',
				'rights' => '1111',
			),
			'switchroomstatus' => array(
				'callback' => '_toggleroomstatus',
				'description' => $this->lang->getLL('command_setroomstatus'),
				'parameters' => array(
					'status' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_param_status'),
						'required' => 1,
					),
				),
				'rights' => $this->extConf['moderatorsAllowSwitchRoomStatus'] ? '0011' : '0001',
			),
/*
			'talk' => array(
				'callback' => '_talk',
				'description' => $this->lang->getLL('command_newroom'),				
				'parameters' => array(
					'name' => array(
						'regExp' =>'/.(.*)/i',
						'description' => $this->lang->getLL('command_newroom_param_name'),
						'required' => 0,
					),
				),
				'rights' => '1111',								
			),						*/
		);
	
	}	

	function _help($params) {

		$out = array();
		$out[] = $this->lang->getLL('command_title').'<br />';
		$out[] = $this->lang->getLL('command_header');
		foreach($this->commands as $name => $data) {

			if($data['hideinhelp'])
				continue;

			if(!$this->grantAccessToCommand($name, $this->env['user']))
				continue;

			$title = '<div class="tx-vjchat-cmd-help-command-title"><span class="tx-vjchat-cmd-help-link" onClick="javascript:tx_vjchat_pi1_js_chat_instance.insertCommand(\'/'.$name.' \');">/'.$name.'</span></div>';
			$parameterList = '';
			$parameterDscr = '';

			if($data['parameters']) {
				foreach($data['parameters'] as $pname => $pdata) {
					$parameterList .= $pdata['required'] ? (' {'.$pname.'}') : (' ['.$pname.']');
					$parameterDscr .= ' - '.$pname.': '.$pdata['description'].'<br />' ;
				}
			}
			
			$commandDscr = $data['description'] ? ('<span class="tx-vjchat-cmd-help-command-descr">'.$data['description'].'</span>') : '';
			$parameterList = $parameterList ? '<span class="tx-vjchat-cmd-help-parameter-list">'.$parameterList.'</span>' : '';

			if($this->extConf['showParameterDescription']) {
				$parameterDscr = $parameterDscr ? ('<span class="tx-vjchat-cmd-help-parameter-descr">'.$parameterDscr.'</span>') : '';
			}
			else
				$parameterDscr = '';
			
			$out[] = '<div class="tx-vjchat-cmd-help-command">'.$title.$parameterList.$commandDscr.$parameterDscr.'</div>';
		}
		return '<div class="tx-vjchat-cmd-help">'.implode('', $out).'</div>';
	}

// >> Begin, Ergänzungen Udo Gerhards
	function _smilies($params)
		{
				// 2006-05-08: Änderung Vincent Tietz
		 		$emoticons = tx_vjchat_lib::getEmoticons(false);

				$out ="";
				$columns = 3;
				$col = 0;
				foreach($emoticons as $iconCode => $image) {
					$out .= '<div class="tx-vjchat-cmd-smileys-text">'.$iconCode.'</div>';
					$out .= '<div class="tx-vjchat-cmd-smileys-image">'.tx_vjchat_lib::formatMessage($iconCode).'</div>';					
					$col++;					
					if($col == $columns) {
						$col = 0;
						$out = $out.'<br style="clear:both;" />';
					}
				}

				$out = '<div class="tx-vjchat-cmd-smileys">'.$out.'</div>';

				return $out;
		}
		
	function _roomlist($params) {
			$roomsArray = $this->db->getRooms();

			$htmlOut = '';
			foreach($roomsArray as $room) {
				if ($this->room->uid != $room->uid && !$room->closed && tx_vjchat_lib::checkAccessToRoom($room, $this->user)) {
					$roomUsers = array();
					$roomUsers = $this->getUserlistOfRoom($room, true);
					$htmlOut.='<div class="tx-vjchat-cmd-roomlist-room"><div class="tx-vjchat-cmd-room-title">'.$room->name.' <span class="tx-vjchat-cmd-roomlist-usercount">('.count($roomUsers).' Users) <a href="javascript:openChatWindow('.$room->uid.');">'.$this->lang->getLL('command_invite_enter_room').'</a></span></div>';
					if (count($roomUsers) >0) {
						$htmlOut .='<ul class=tx-vjchat-cmd-roomlist-userlist">';
						foreach($roomUsers as $user)
									$htmlOut .= '<li class="tx-vjchat-cmd-roomlist-user">'.$this->getUsername($user).'</li>';
						$htmlOut .= '</ul>';
					}
					$htmlOut .= '</div>';
				}
			}
			return $htmlOut;			
	}
// >> End, Ergänzungen Udo Gerhards



	function _who($params) {
		$userNames = $this->getUserinfoOfRoom($this->room,', ', ': ', true);
		$htmlOut = '<div class="tx-vjchat-cmd-who"><span class="tx-vjchat-cmd-who">'.count($userNames).' Users:</div>';
		$htmlOut .='<ul class=tx-vjchat-cmd-who-userlist">';
		foreach($userNames as $user)
			$htmlOut .= '<li class="tx-vjchat-cmd-who-user">'.$user.'</li>';
		$htmlOut .= '</ul></div>';
		return $htmlOut;
	}


	function _whois($params) {
	
		// get informations about self
		if(!$params[1]) {
			return implode(', ',$this->getUserInfo($this->room, $this->env['user'], ': '));
		} 
		// get userid if username is givem
		else {
			$users = $this->getFeUserByInput($params[1]);
			if(!$users || count($users) == 0)
				return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
			if(count($users) > 1)
				return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
			$user = $users[0];
			return implode(', ',$this->getUserInfo($this->room, $user, ': '));
		}
	}

	function _msg($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];

		unset($params[1]);
		$message = implode(' ',$params);
		$this->debugMessage('/msg: '.$message);
		$this->putMessage($message, $this->lastMessageId, $user['uid']);

	}

	function _ban($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
			
		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_ban_ok'), ($this->getUsername($user)), $this->getUsername($this->user));
		unset($params[1]);
		unset($params[2]);
		$systemmessage .= $params[3] ? (' '.sprintf($this->lang->getLL('command_ban_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);

		sleep(5);

		// and quit
		$this->db->putMessage($this->room->uid, '/quit', $user['uid'], true);

		// and ban
		$this->db->banUser($this->room, $user['uid']);
		
		return 'OK';
	
	}

	function _kick($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];

		$time = $params[2] ? $params[2] : $this->commands['kick']['parameters']['time']['default'];

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_kick_ok'), tx_vjchat_lib::getChatUserName($this->room, $user), $this->getUsername($this->user), $time);
		unset($params[1]);
		unset($params[2]);
		$systemmessage .= $params[3] ? (' '.sprintf($this->lang->getLL('command_kick_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);

		sleep(5);

		// and kick
		$this->db->kickUser($this->room->uid, $user['uid'], $time);
		
		return 'OK';
	}
	
	function _redeem($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
		
		$this->db->redeemUser($this->env['room_id'], $user['uid']);

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_redeem_ok'), ($this->getUsername($user)), $this->getUsername($this->user));
		unset($params[1]);
		$systemmessage .= $params[2] ? (' '.sprintf($this->lang->getLL('command_redeem_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);

		return 'OK';
	}

	function _quit($params) {

		// send a system notification message
		$systemmessage = sprintf($this->lang->getLL('command_quit_ok'), $this->getUsername($this->user));
		$systemmessage .= $params[1] ? (' '.sprintf($this->lang->getLL('command_quit_reason'), implode(' ',$params))) : '';
		$this->db->putMessage($this->env['room_id'], $systemmessage);
		sleep(2);

		// and quit
		$this->db->putMessage($this->env['room_id'], '/quit', '', $this->user['uid'], true);
		return 'OK';
	}
	
	function _makesession($params) {
	
		$startid = $params[1];
		$endid = $params[2];		

		unset($params[1]);
		unset($params[2]);
		
		$name = implode(' ',$params);
		return $this->db->makesession($this->room->uid, $name, '', 0, $startid, $endid);
	}

	function _makeexpert($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
		
		$res = $this->db->makeExpert($this->room, $user['uid']);
		if($res) {
			$message = 'User '.($this->getUsername($user)).' is now an expert. Initiated by '.($this->getUsername($this->user)).'.';
			$this->db->putMessage($this->env['room_id'], $message);
			return 'OK';
		} 
		else 
			return '<span class="tx-vjchat-error">ERROR OR NOTHING TO DO</span>';


	}
	
	function _makeuser($params) {
		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
		
		$res = $this->db->makeUser($this->room, $user['uid']);
		if($res) {
			$message = 'User '.($this->getUsername($user)).' is set to a normal user. Initiated by '.($this->getUsername($this->user)).'.';
			$this->db->putMessage($this->env['room_id'], $message);
			return 'OK';
		} 
		else 
			return '<span class="tx-vjchat-error">ERROR OR NOTHING TO DO</span>';

	}	
	
	function _cleanuproom() {
		$res = $this->db->cleanUpRoom($this->room);
		return $res ? ($res.' Entries deleted') : 'NOTHING DELETED';
	}

	function _cleanupall() {
		$res = $this->db->cleanUpAllRooms();
		return $res ? ($res.' Entries deleted') : 'NOTHING DELETED';
	}

	function _togglestatus($params) {

		if($params[2]) {
			$users = $this->getFeUserByInput($params[1]);
			if(!$users || count($users) == 0)
				return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
			if(count($users) > 1)
				return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
			$user = $users[0];
		
			$res = $this->db->setUserStatus($this->room, $user, $params[2]);
		}
		else {
			$res = $this->db->setUserStatus($this->room, $this->user, $params[1]);		
		}
		return $res ? 'TOGGLED' : '<span class="tx-vjchat-error">ERROR</<span>';
	}
	
	function _toggleroomstatus($params) {
		$res = $this->db->setRoomStatus($this->room, $params[1]);		
		return $res ? ('TOGGLED: '.$params[1].'='.$res) : '<span class="tx-vjchat-error">ERROR</<span>';
	}	
	
	function _setmessagestyle($params) {
		
		$newstyle = $params[1];
		$this->debugMessage('setmessagestyle: '.implode(',',$params));
		
		if($newstyle == $this->user['tx_vjchat_chatstyle'] || $newstyle == 0) {
			$newstyle = 0;
			$this->db->setMessageStyle($this->user['uid'], $newstyle);
			return $this->lang->getLL('default_chatstyle');
		}

		$this->db->setMessageStyle($this->user['uid'], $newstyle);
		return $this->lang->getLL('chatstyle').': '.$newstyle.'.';
	}
	
	function _newroom($params) {

		$username = $this->getUsername();

		if(!$params[1]) {
			$roomName = sprintf($this->lang->getLL('command_newroom_room_default_title'), $username);
		}
		else 
			$roomName = implode(' ',$params);
		
		$newRoom = new tx_vjchat_room();
		$newRoom->pid = $this->room->pid;		
		$newRoom->name = $this->db->getUniqueRoomName(tx_vjchat_lib::decodeUsername($roomName));
		$newRoom->superusergroup = $this->room->superusergroup;
		$newRoom->description = sprintf($this->lang->getLL('command_newroom_room_default_description'), $username);
		$newRoom->owner = $this->user['uid'];
		$newRoom->moderators = $this->user['uid'];
		$newRoom->private = true;		
		$newRoom->showuserinfo_users = $this->room->showuserinfo_users;
		$newRoom->showuserinfo_moderators = $this->room->showuserinfo_moderators;
		$newRoom->showuserinfo_experts = $this->room->showuserinfo_experts;				
		$newRoom->hidden = $this->extConf['hidePrivateRooms'];
		$newRoom->chatUserNameFieldSuperusers = $this->room->chatUserNameFieldSuperusers;
		$newRoom->chatUserNameFieldExperts = $this->room->chatUserNameFieldExperts;
		$newRoom->chatUserNameFieldModerators = $this->room->chatUserNameFieldModerators;
		$newRoom->chatUserNameFieldUsers = $this->room->chatUserNameFieldUsers;
	
		$newRoom->uid = $this->db->createNewRoom($newRoom);
		$this->db->updateUserInRoom($newRoom->uid, $this->user['uid']);
		
		$msg = sprintf($this->lang->getLL('command_newroom_ok'), $newRoom->name);
		$msg .= ' <a href="javascript:openChatWindow('.($newRoom->uid).');" onClick="javascript:openChatWindow('.($newRoom->uid).'); return false;">'.$this->lang->getLL('command_invite_enter_room').'</a>' ;
		$msg .= '<script language="JavaScript" type="text/javascript">openChatWindow('.($newRoom->uid).')</script>';
		
		return $msg;
	}

	function _do_invite($user, $room) {
	
		$this->db->addMemberToRoom($room, $user['uid']);

		if($params[2]) {
			unset($params[1]);
			$msg = implode(' ',$params);
		}
		else {
			$msg = sprintf($this->lang->getLL('command_invite_default_message'), $this->getUsername(), $this->getUsername($user) , $room->name);
		}
		
		$msg = $msg.' <a href="javascript:openChatWindow('.$room->uid.');">'.$this->lang->getLL('command_invite_enter_room').'</a>' ;
		
		$rooms = $this->db->getRoomsOfUser($user['uid']);

		if(count($rooms) == 0) {
			return sprintf($this->lang->getLL('command_invite_user_not_online'), $this->getUsername($user));
		}

		// send private system messages to all rooms
		foreach($rooms as $room) {
			$this->db->putMessage($room->uid, $msg, 0, $this->user, true, 0, $user['uid']);
		}
		
		return sprintf($this->lang->getLL('command_invite_enter_room_ok'), $this->getUsername($user), count($rooms));
	
	}
	
	function _invite($params) {
	
		$users = $this->getFeUserByInput($params[1]);
		
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
		
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		
		return $this->_do_invite($user, $this->room);
	}
	
	function _recentinvite($params) {

		$users = $this->getFeUserByInput($params[1]);
		if(!$users || count($users) == 0)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		if(count($users) > 1)
			return sprintf($this->lang->getLL('command_error_user_not_unique'), $params[1]);
		$user = $users[0];
		
		if(!$user)
			return sprintf($this->lang->getLL('command_error_user_not_found'), $params[1]);
		
		$rooms = $this->db->getRoomsOfUserAsOwner($this->user['uid']);
		
		if(count($rooms) == 0)
			return 'No room found';
		
		return $this->_do_invite($user, $rooms[count($rooms)-1]);		
	
	}
	
	function checkParams($params, $data) {

		if(!$data['parameters'])
			return true;
			
		$number = 1;
		foreach($data as $name => $paramData) {
			if($paramData['regExp'] && !preg_match($paramData['regExp'], $params[$number]))
				return sprintf($this->lang->getLL('command_wrong_parameter'), $name, $paramData['description']);
			$number++;
		}
		return true;
	}
	
	function grantAccessToCommand($command) {
		$denied = true;

		if($this->commands[$command]['rights'][0])
			$denied = false;
	
		if($this->commands[$command]['rights'][1] && tx_vjchat_lib::isExpert($this->room, $this->user['uid']))
			$denied = false;
	
		if($this->commands[$command]['rights'][2] && tx_vjchat_lib::isModerator($this->room, $this->user['uid']))
			$denied = false;

		if($this->commands[$command]['rights'][3] && tx_vjchat_lib::isSuperuser($this->room, $this->user))
			$denied = false;

		return !$denied;
	}
	
	function performCommand($lines) {

		$this->timer->start('performCommand');
	
		if(!tx_vjchat_lib::checkAccessToRoom($this->room, $this->env['user']))
			return $this->lang->getLL('error_room_access_denied');

		$lines = t3lib_div::trimExplode(chr(10), $lines);
		
		foreach($lines as $line) {

			$this->debugMessage(__LINE__.$line);

			// replace ' ' in quoted Strings by '_'
			if (preg_match('/ \'(.*)\'/i', $line, $matches)) {
				$line = str_replace('*', '', $line);
				$line = str_replace($matches[1], urlencode($matches[1]), $line);
			}

			//$this->debugMessage(__LINE__.print_r($this->getAllOnlineUsers()));
			
			// encode usernames, e.g., to avoid splitting user names wit space
			$line = tx_vjchat_lib::findAndEncodeChatUserNames($this->room, $line, $this->getFeUsers());
			
			$this->debugMessage(__LINE__.$line);
			
			// check if message contains commands
			$parts = t3lib_div::trimExplode(' ', $line);

			$found = false;
			
			foreach($this->commands as $command => $data) {
				// parse command
				if($parts[0] == ('/'.$command)) {
					$found = true;
					// check rights
					unset($parts[0]);
					
					if(!$this->grantAccessToCommand($command, $this->env['user'])) {
						$out .= '<span class="tx-vjchat-error">'.$this->lang->getLL('error_access_denied').'</span>';
						continue;
					}
										
	
					// check params
					$paramResult = $this->checkParams($parts, $data['parameters']);
					if($paramResult === true) {
	
						// perform command
						$cmdResult = $this->$data['callback']($parts);

						// decode user names for command result
						foreach($parts as $key => $part) {
							$parts[$key] = tx_vjchat_lib::findAndDecodeChatUserNames($this->room, $parts[$key], $this->getFeUsers());
						}
						
						if(!$data['hidefeedback'])
							$out .= '<span class="tx-vjchat-ok">/'.$command.' '.implode(' ',$parts).': '.$cmdResult.'</span>';
						else
								// error
							if($cmdResult)
								$out .= '<span class="tx-vjchat-error">'.sprintf($cmdResult,$parts[0]).'</span>';
					}
					else
						$out .= '<span class="tx-vjchat-ok">/'.$command.' '.implode(' ',$parts).': '.$paramResult.'</span>';
				}

			}

			if(!$found) 
				$out .= '<span class="tx-vjchat-error">'.sprintf($this->lang->getLL('command_not_found'),$parts[0]).'</span>';

		}
		
		$this->timer->stop('performCommand');
		
		return $this->returnMessage($out);
		
	
	}

	/**********************************************************************************************/
	// GENERAL HELPER FUNCTIONS
	/**********************************************************************************************/	

	/**
	  * This is for getUserlist() only
	  */
	function getUserlistOfRoom($room, $roomlistMode = false) {
		
		$users = $this->db->getFeUsersOfRoom($room);
		$theValue = array();
		
		foreach($users as $user) {

			if(!$user || !$user['uid'])
				continue;
				
			$auser = array();
			
			$type = tx_vjchat_lib::getUserTypeString($room, $user);
			
			$auser['uid'] = $user['uid'];
			$auser['username'] = tx_vjchat_lib::getChatUserName($room, $user);
			$auser['style'] = $user['tx_vjchat_chatstyle'];
			$auser['type'] = tx_vjchat_lib::getUserTypeString($room, $user);

			$snippets = $this->db->getSnippets($room->uid, $user['uid']);
			
			if($snippets['userlistsnippet'])
				$auser['userlistsnippet'] = $snippets['userlistsnippet'];
			
			if($snippets['tooltipsnippet'])	
				$auser['tooltipsnippet'] = $snippets['tooltipsnippet'];

			$details = $room->getDetailsField($type);
			$auser['additonalinformation'] = array();
			foreach($details as $key) {
				if($room->showDetailOf($type,$key)) {
					$auser['additonalinformation'][$key] = $user[$key];
				}
			}			
			
			$theValue[] = $auser;
			
		}
		
		return $theValue;
		
	}
	
	/**
	  * This is for /who 
	  */
	function getUserinfoOfRoom($room, $userNamesGlue = ': ', $userNamesFieldGlue = ', ') {

		$users = $this->db->getFeUsersOfRoom($room);
		//$users = $room->members;
		
		$userNames = array();
		foreach($users as $user) {
		
			if(!$user || !($this->getUsername($user)))
				continue;
			
			$parts = $this->getUserInfo($room, $user, $userNamesFieldGlue, $onlyAllowedFields);
			$userNames[] = implode($userNamesGlue, $parts);
		}
		return $userNames;		
	}

	function getUserInfo($room, $user, $userNamesFieldGlue) {
	
		// user, moderator or expert
		$type = tx_vjchat_lib::getUserTypeString($room, $user);
	
		$parts = array();
		$parts['username'] = '<strong>'.htmlentities($this->getUsername($user)).'</strong>';

		$details = $room->getDetailsField($type);
		foreach($details as $key) {
			if($room->showDetailOf($type,$key))
				$parts[] = $key.($userNamesFieldGlue.$user[$key]);
		}
		
		return $parts;	
	}

	function getFeUsers() {
		if(!$this->feUsersOfRoom)
			$this->feUsersOfRoom = $this->db->getFeUsersOfRoom($this->room, true, true);
		return $this->feUsersOfRoom;
	}

	function getFeUserByInput($input) {
		$chatUsername = tx_vjchat_lib::decodeUsername($input);
		return tx_vjchat_lib::getUserByChatUserName($this->room, $chatUsername, $this->getFeUsers());
	}
	
	function getUsername($user = NULL) {
		if(!$user)
			return tx_vjchat_lib::getChatUserName($this->room, $this->user);
		else
			return tx_vjchat_lib::getChatUserName($this->room, $user);
	}

	function utf8_encode_array(&$item, $key) {
		return utf8_encode($item);
	}
	
	function array_walk_recursive(&$input, &$callback, $userdata = null) {
		foreach($input as $key => $value) {
			if (is_array($value)) {
				if(!$this->array_walk_recursive(&$input[$key], $callback, $userdata)) {
					return false;
				}
			}
			else {
				$input[$key] = call_user_func(&$callback, &$value, $key, $userdata);
			}
		}
		return true;
	}

 
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_chat.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_chat.php']);
}

?>