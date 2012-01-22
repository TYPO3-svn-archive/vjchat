<?php

//require_once(PATH_t3lib.'class.t3lib_div.php');

require_once('class.tx_vjchat_room.php');
require_once('class.tx_vjchat_session.php');
require_once('class.tx_vjchat_entry.php');

class tx_vjchat_db {

	var $db;
	var $extCONF;

	function tx_vjchat_db() {
		$this->db = $GLOBALS['TYPO3_DB'];
		$this->extCONF = tx_vjchat_lib::getExtConf();
	}
	
	function setDebug($debug) {
		$this->db->debugOutput = $debug;
		$this->db->store_lastBuiltQuery = $debug;
	}
	
	/**
	  * @param int Page ID (optional), if not set it returns all rooms 
	  * @return Array Room
	  */
	
	function getRooms($pidList = NULL) {
		$rooms_public = $this->_getRooms($pidList, false);
		$rooms_private = $this->_getRooms($pidList, true);
		return array_merge($rooms_public, $rooms_private);
	}

	function _getRooms($pidList = NULL, $getPrivate = false, $isSuperuser = false, $getHidden = false) {
	
		$this->cleanUpRooms();
	
		$where = $pidList ? (' pid IN ('.$pidList.') AND ') : false;
		if(!$where)
			$where = $this->extCONF['pids.']['rooms'] ? ('pid IN ('.$this->extCONF['pids.']['rooms'].') AND ') : '';

		if(!$isSuperuser) {
			$where .= ' 1=1 ';
			$where .= $this->enableFields('tx_vjchat_room', $getHidden);
		
			$where .= $getPrivate ? ' AND private = 1' : ' AND private = 0';
		}
	
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', $where, '' , 'sorting');
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		$rooms = array();
		while($row = $this->db->sql_fetch_assoc($res)) {

			$room = t3lib_div::makeInstance('tx_vjchat_room');
			$room->fromArray($row);
	
			$rooms[] = $room;

		}
		
		return $rooms;
		
	}
	
	function getRoomsOfPage($pageId) {
	
		$pageId = intval($pageId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', 'page = '.$pageId, '' , 'sorting');

		$rooms = array();
		while($row = $this->db->sql_fetch_assoc($res)) {

			$room = t3lib_div::makeInstance('tx_vjchat_room');
			$room->fromArray($row);
	
			$rooms[] = $room;

		}
		
		return $rooms;
		
	}
	
	/**
	  * removes private rooms
	  */
	function cleanUpRooms() {
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room','private = 1');	
		while($row = $this->db->sql_fetch_assoc($res)) {
			// clean up, no messages and a longer idle time
			$this->cleanUpUserInRoom($row['uid'], $this->extCONF['maxAwayTime'], false);
			// look for private empty rooms
			$userCount = $this->getUserCountOfRoom($row['uid'], true);
			if( ($userCount == 0) && $this->extCONF['deletePrivateRoomsIfEmpty'] ) {
				$this->deleteRoom($row['uid']);
			}
		}
	}
	
	function getRoom($uid) {
	
		$uid = intval($uid);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', 'uid = \''.$uid.'\' AND deleted = 0');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if(!$row = $this->db->sql_fetch_assoc($res))
			return false;

		$room = t3lib_div::makeInstance('tx_vjchat_room');
		$room->fromArray($row);
		return $room;	
	}
	
	function getRoomsOfUser($userId) {
	
		$userId = intval($userId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room_feusers_mm, tx_vjchat_room', 'uid_foreign = '.$userId.' AND uid_local = tx_vjchat_room.uid AND in_room = 1' );

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if(!$this->db->sql_num_rows($res))
			return array();
		
		$rooms = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			$room = t3lib_div::makeInstance('tx_vjchat_room');
			$room->fromArray($row);
			$rooms[] = $room;
		}
		
		return $rooms;		
	
	}

	function getRoomsOfUserAsOwner($userId) {
	
		$userId = intval($userId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', 'owner = '.$userId, '', 'uid' );

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if(!$this->db->sql_num_rows($res))
			return array();
		
		$rooms = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			$room = t3lib_div::makeInstance('tx_vjchat_room');
			$room->fromArray($row);
			$rooms[] = $room;
		}
		
		return $rooms;		
	
	}

	function createNewRoom($room) {
		$data = $room->toArray();
		$data['crdate'] = time();
		$data['tstamp'] = time();		
		$res = $this->db->exec_INSERTquery('tx_vjchat_room', $data);
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_insert_id();		
	}
	
	function deleteRoom($roomId) {
	
		$roomId = intval($roomId);
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.$roomId, array('deleted' => 1));
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_affected_rows();		
	}
	
	function getUniqueRoomName($roomName) {

		$i = 0;
		$oldRoomName = $roomName;

		while(true) {
			$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', 'name = \''.$roomName.'\' '.$this->enableFields('tx_vjchat_room'));
		
			if(!$res)
				print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
			
			// if name is not unique append #i
			if($this->db->sql_num_rows($res)) {
				$i++;
				$roomName = $oldRoomName.' #'.$i;
			}
			// name is unique, quit
			else {
				break;
			}
		}
		
		return $roomName;
		
	}

	function getSession($sessionId) {
	
		$sessionId = intval($sessionId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_session', 'uid = '.$sessionId);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if(!$row = $this->db->sql_fetch_assoc($res))
			return false;

		$session = t3lib_div::makeInstance('tx_vjchat_session');
		$session->fromArray($row);
		return $session;	
	}	

	function getSessionsCountOfRoom($roomId) {
	
		$roomId = intval($roomId);
	
		$where = ' room = '.$roomId;
		$where .= $this->enableFields('tx_vjchat_session');
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_session', $where);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		return $this->db->sql_num_rows($res);
	}

	function getSessionsOfRoom($roomId) {
	
		$roomId = intval($roomId);
	
		if(!$roomId)
			return array();
	
		$where = ' room = '.$roomId;
		$where .= $this->enableFields('tx_vjchat_session');
	
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_session', $where, '', 'sorting');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		$sessions = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			$session = t3lib_div::makeInstance('tx_vjchat_session');
			$session->fromArray($row);
			$sessions[] = $session;
		}
		
		return $sessions;

	}
	
	function getSessions() {
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_session', '', '', 'sorting');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		$sessions = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			$session = t3lib_div::makeInstance('tx_vjchat_session');
			$session->fromArray($row);
			$sessions[] = $session;
		}
		
		return $sessions;
	
	}
	
	function getEntriesOfSession($session) {
	
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_entry', '(uid >= '.(intval($session->startid)).') AND (uid <= '.(intval($session->endid)).') AND (room = '.$session->room.') AND cruser_id = feuser '.$this->enableFields('tx_vjchat_entry'), '', 'uid');	

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		$entries = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			$entry = t3lib_div::makeInstance('tx_vjchat_entry');
			$entry->fromArray($row);
			$entries[] = $entry;
		}
		
		return $entries;
	}
	
	function getEntriesCountOfSession($session) {
		return count($this->getEntriesOfSession($session));
	}
	
	function getUserCountOfRoom($roomId = null, $getHidden = false) {
	
		$where = '';
	
		if($roomId && !is_array($roomId))
			$where .=  'uid_local = '.(intval($roomId)).' AND ';
			
		if($roomId && is_array($roomId))
			$where .=  'uid_local IN ('.(implode(',',$roomId)).') AND ';
		
		$where .= 'tstamp <= '.$this->getTime();
		
		if(!$getHidden) {
			$where .=  ' AND invisible = 0';
		}
		
		$res = $this->db->exec_SELECTquery('count(*)','tx_vjchat_room_feusers_mm', $where.' AND in_room = 1');
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		$row = $this->db->sql_fetch_row($res);
			
		return $row[0];
		//return $this->db->sql_num_rows($res);
	}

	function updateUserInRoom($roomId, $userId, $isSuperuser = false, $enterlabel = '') {

		if(!$userId || !$roomId)
			return;
			
		$roomId = intval($roomId);
		$userId = intval($userId);

		$user = $this->getFeUser($userId);
			
		//check if User is already memberOf Room
		// if not try to add
		if(!$this->isMemberOfRoom($roomId, $userId, false)) {
			// check first if room is full
			$room = $this->getRoom($roomId);

			// allow superusers to access room even if it is 'full'
			if($this->isRoomFull($room) && !$isSuperuser)
				return 'full';

			$invisible = ($this->extCONF['hideSuperusers'] && $isSuperuser) ? 1 : 0;

			$data = array(
				'uid_local' => $roomId,
				'uid_foreign' => $userId,
				'tstamp' => $this->getTime(),
				'invisible' => $invisible,
				'in_room' => 1,
			);

			$res = $this->db->exec_INSERTquery('tx_vjchat_room_feusers_mm', $data);			

			if(!$res)
				print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

			// system message for all users, except if user is invisible
			if(!$invisible)
				$this->putMessage($roomId, sprintf($enterlabel,$user['username']));			
			
			return "entered";
		}

		// otherwise update time (only if user is not banned, this means if time is less than current time)
		else {
		
			if($this->getUserStatus($roomId, $userId, 'in_room') == 0)
				$this->putMessage($roomId, sprintf($enterlabel,$user['username']));
			
			$data = array('tstamp' => $this->getTime(),'in_room' => 1);
			$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId.' AND tstamp < '.$this->getTime(), $data);
		}
		
		return true;
		
	}

	/**
	  * Removes all users from all rooms if they idle for 30 seconds
	  */
	function cleanUpUserInRoom($roomId, $idle = 15, $systemMessageOnLeaving = true, $leaveMessage = '%s leaves chat.', $removeSystemMessagsOlderThan = 60) {

		if(!$roomId)
			return NULL;
			
		$roomId = intval($roomId);
		$idle = intval($idle);
		$removeSystemMessagsOlderThan = intval($removeSystemMessagsOlderThan);

//		$data = array('tx_vjchat_inchatroom' => '');
		
		$res = $this->db->exec_SELECT_mm_query('fe_users.*','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND (tx_vjchat_room.uid = '.$roomId.') AND ( (tx_vjchat_room_feusers_mm.tstamp) < '.($this->getTime()-$idle).')');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if($this->db->sql_num_rows($res)) {
			while($row = $this->db->sql_fetch_assoc($res)) {
				$this->leaveRoom($roomId, $row['uid'], $systemMessageOnLeaving, $leaveMessage);
			}
		}

		if($removeSystemMessagsOlderThan) {
			$res = $this->db->exec_DELETEquery('tx_vjchat_entry', ' (feuser = 0 OR cruser_id = 0) AND ( (crdate) < '.($this->getTime()-$removeSystemMessagsOlderThan).')');		

			if(!$res)
				print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		}
		
	}
		
	/**
	  * We bann a user from this room by setting tstamp up to this time when he can enter this room again
	  */
	function kickUser($roomId, $userId, $time = 30) {
		if(!$roomId || !$userId)
			return false;
		
		$roomId = intval($roomId);
		$userId = intval($userId);		
		
		$data = array('tstamp' => ($this->getTime()+($time*60)));		
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId, $data);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return true;
	}

	function banUser($room, $userId) {
		
		if(!$room || !$userId)
			return false;

		$roomId = intval($roomId);
		$userId = intval($userId);			
			
		$banned = $room->bannedusers ? ($room->bannedusers.','.$userId) : $userId;
		$data = array('bannedusers' => t3lib_div::uniqueList($banned));
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.$room->uid, $data);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		return true;
		
	}

	/**
	  * Revive user by setting a proper timestamo
	  */
	function redeemUser($roomId, $userId) {

		if(!$roomId || !$userId)
			return false;

		$roomId = intval($roomId);
		$userId = intval($userId);			
			
		$room = $this->getRoom($roomId);
		
		// is banned? remove from banned list
		$bannedusers = t3lib_div::rmFromList($userId, $room->bannedusers);
		$data = array('bannedusers' => $bannedusers);		
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.$roomId, $data);

		// if user was kicked, update time
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId);
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		if($row = $this->db->sql_fetch_assoc($res)) {
			if($row['tstamp'] > $this->getTime()) {
				$data = array('tstamp' => $this->getTime());		
				$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId, $data);
			}
		}
		
		return true;
	}
	
	function leaveRoom($roomId, $userId, $systemMessageOnLeaving = true, $leaveMessage = '%s leaves chat') {
		
		if(!$roomId || !$userId)
			return false;
		
		$roomId = intval($roomId);
		$userId = intval($userId);		
		
		// do not delete kicked users
		if($this->isUserKicked($roomId, $userId))
			return false;
		
		$user = $this->getFeUser($userId);
	
		if($systemMessageOnLeaving && !$this->getUserStatus($roomId, $userId, 'invisible') && $this->getUserStatus($roomId, $userId, 'in_room'))
			$this->putMessage($roomId, sprintf($leaveMessage, $user['username']));

		$data = array('in_room' => 0);		
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId, $data);
			
			// definitely delete unnecessary  entries
		$idle = 60 * 10;
		//$res = $this->db->exec_DELETEquery('tx_vjchat_room_feusers_mm', ' uid_local = '.$roomId.' AND uid_foreign = '.$userId.' AND (tstamp < '.($this->getTime()-$idle).')');		
		$res = $this->db->exec_DELETEquery('tx_vjchat_room_feusers_mm', 'tstamp < '.($this->getTime()-$idle));		
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

	}	
	

	function getFeUsersOfRoom($room, $getHidden = false) {

		if(!$room)
			return NULL;
			
		$users = array();
/*
		// do not get kicked users (tstamp > $this->getTime())

		// get experts
		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$room->uid.' AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND FIND_IN_SET(fe_users.uid, tx_vjchat_room.experts)', '', 'username');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;
			$users[$row['uid']] = $row;
		}

		// get moderators
		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$room->uid.' AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND FIND_IN_SET(fe_users.uid, tx_vjchat_room.moderators)', '', 'username');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;
			$users[$row['uid']] = $row;
		}

		// get users
		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$room->uid.' AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND NOT FIND_IN_SET(fe_users.uid, tx_vjchat_room.moderators) AND NOT FIND_IN_SET(fe_users.uid, tx_vjchat_room.experts)', '', 'username');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;
			$users[$row['uid']] = $row;
		}
	*/
	
		$users = $this->getOnlineUsers($room->uid);
		$experts = $this->getOnlineExperts($room->uid);
		$moderators = $this->getOnlineModerators($room->uid);				
		
		return array_merge($users, $experts, $moderators);
		
	}
	

	function getOnlineSuperusers($roomId, $getHidden = false) {
		if(!$roomId)
			return NULL;
		
		$roomId = intval($roomId);

		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$roomId.' AND FIND_IN_SET(fe_users.uid,tx_vjchat_room.superusers) AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND in_room = 1');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		//$res = $this->db->exec_SELECTquery('*','fe_users', ' (FIND_IN_SET(\''.$roomId.'\', tx_vjchat_inchatroom)) > 0', '', 'username');
		$users = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;		
			$users[$row['uid']] = $row;
		}
		return $users;
	}	
	
	function getOnlineExperts($roomId, $getHidden = false) {
		if(!$roomId)
			return NULL;
			
		$roomId = intval($roomId);

		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$roomId.' AND FIND_IN_SET(fe_users.uid,tx_vjchat_room.experts) AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND in_room = 1');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		//$res = $this->db->exec_SELECTquery('*','fe_users', ' (FIND_IN_SET(\''.$roomId.'\', tx_vjchat_inchatroom)) > 0', '', 'username');
		$users = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;		
			$users[$row['uid']] = $row;
		}
		return $users;
	}

	function getOnlineModerators($roomId, $getHidden = false) {
		if(!$roomId)
			return NULL;
			
		$roomId = intval($roomId);

		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$roomId.' AND FIND_IN_SET(fe_users.uid,tx_vjchat_room.moderators) AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND in_room = 1');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		//$res = $this->db->exec_SELECTquery('*','fe_users', ' (FIND_IN_SET(\''.$roomId.'\', tx_vjchat_inchatroom)) > 0', '', 'username');
		$users = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;
			$users[$row['uid']] = $row;
		}
		return $users;
	}
	
	function getOnlineUsers($roomId, $getHidden = false) {
		
		if(!$roomId)
			return NULL;
			
		$roomId = intval($roomId);

		// do not get kicked users (tstamp > $this->getTime())

		// get experts
//		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$room->uid.' AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND FIND_IN_SET(fe_users.uid, tx_vjchat_room.experts)', '', 'username');


		$res = $this->db->exec_SELECT_mm_query('fe_users.*,tx_vjchat_room_feusers_mm.invisible as invisible','tx_vjchat_room','tx_vjchat_room_feusers_mm','fe_users', ' AND tx_vjchat_room.uid = '.$roomId.' AND NOT FIND_IN_SET(fe_users.uid,tx_vjchat_room.moderators) AND NOT FIND_IN_SET(fe_users.uid,tx_vjchat_room.experts) AND tx_vjchat_room_feusers_mm.tstamp <= '.$this->getTime().' AND in_room = 1');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		//$res = $this->db->exec_SELECTquery('*','fe_users', ' (FIND_IN_SET(\''.$roomId.'\', tx_vjchat_inchatroom)) > 0', '', 'username');
		$users = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			if($row['invisible'] && !$getHidden)
				continue;
			$users[$row['uid']] = $row;
		}
		return $users;
	}	
	
	function isUserKicked($roomId, $userId) {
	
		if(!$userId || !$roomId)
			return false;
		
		$roomId = intval($roomId);
		$userId = intval($userId);
	
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room_feusers_mm',' uid_local = '.$roomId.' AND uid_foreign = '.$userId.' AND tstamp > '.$this->getTime());

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if($row = $this->db->sql_fetch_assoc($res))
			return round(($row['tstamp'] - $this->getTime()) / 60);

		return false;
	}
	
	function isRoomFull($room) {
		if(!$room->maxusercount)
			return false;
		
		if($this->getUserCountOfRoom($room->uid) >= $room->maxusercount)
			return true;
		
		return false;
		
	}

	function isMemberOfRoom($roomId, $userId, $depend_on_inroom = true) {
		if(!$roomId || !$userId)
			return NULL;
			
		$roomId = intval($roomId);
		$userId = intval($userId);			

		$inroom = $depend_on_inroom ? ' AND in_room = 1' : '';
		
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room_feusers_mm',' uid_local = '.$roomId.' AND uid_foreign = '.$userId.$inroom);
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		return $this->db->sql_num_rows($res);
	}

	function getTime() {

/*		if(!$this->extCONF['serverTimeOffset'])
			return time();
		
		$time = strtotime($this->extCONF['serverTimeOffset'], time());
		
		if($time == -1)
			return time();
		
		return $time;*/
		
		return time();
	}

	function putMessage($roomId, $msg, $style = 0, $user = NULL, $hidden = false, $cruser_id = 0, $tofeuserid = 0) {

		$userId = is_array($user) ? $user['uid'] : $user;

		$roomId = intval($roomId);
		$userId = intval($userId);		
		
		$data = array(
			'crdate' => $this->getTime(),
			'tstamp' => $this->getTime(),
			'cruser_id' => $cruser_id,
			'feuser' => $userId ? $userId : '',
			'tofeuser' => $tofeuserid,
			'room' => $roomId,
			'entry' => $msg,
			'hidden' => ($hidden ? '1' : '0'),
			'style' => $style,
			'pid' => $this->extCONF['pids.']['entries'] ? $this->extCONF['pids.']['entries'] : 0,
			);
			
		$res = $this->db->exec_INSERTquery('tx_vjchat_entry', $data);
	}

	/**
	  * @return Array all messages in this room after $id
	  */
	function getEntries($room, $id = 0) {


#		$hidden = $showHidden ? 'hidden=1' : 'hidden=0';
#		$deleted = $showHidden ? 'deleted=1' : 'deleted=0';		

		if(!$id)
			$id = 0;
			
		$id = intval($id);

		$where = ' room = '.(intval($room->uid)).' AND uid > '.$id. ' AND deleted = 0';

		$res = $this->db->exec_SELECTquery('*','tx_vjchat_entry', $where, '', 'crdate,uid');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		$entries = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			//var_dump($row);
			$entry = t3lib_div::makeInstance('tx_vjchat_entry');
			$entry->fromArray($row);
			$entries[] = $entry;
		}
		
		return $entries;
	}
	
	function getEntriesAfterTime($room, $time) {

		$time = intval($time);
		$where = ' room = '.(intval($room->uid)).' AND crdate >= '.$time. ' AND deleted = 0';

		$res = $this->db->exec_SELECTquery('*','tx_vjchat_entry', $where, '', 'crdate,uid');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		$entries = array();
		while($row = $this->db->sql_fetch_assoc($res)) {
			//var_dump($row);
			$entry = t3lib_div::makeInstance('tx_vjchat_entry');
			$entry->fromArray($row);
			$entries[] = $entry;
		}
	
		return $entries;
	
	}
	
	function getLatestEntryId($room, $time) {
	
		$time = intval($time);

		$where = ' room = '.(intval($room->uid)).' AND crdate >= '.$time. ' AND deleted = 0';
		
		$res = $this->db->exec_SELECTquery('min(uid)','tx_vjchat_entry', $where, '');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		while($row = $this->db->sql_fetch_row($res)) {
			if($row[0])
				return $row[0]-1;
		}

		$where = ' room = '.$room->uid.' AND deleted = 0';
		$res = $this->db->exec_SELECTquery('max(uid)','tx_vjchat_entry', $where, '');

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		while($row = $this->db->sql_fetch_row($res)) {
			if($row[0])
				return $row[0];
		}

		return 0;

	}
	
	function makeSession($roomId, $name, $description = '', $hidden = 1, $start = -1, $end = -1) {

		$roomId = intval($roomId);
	
		if( ($start === -1) || ($end === -1) )
			return 'DB: Invalid parameters';

		if($start >= $end)
			return 'DB: firstId must be less than lastId';
			
		if(!is_numeric($start) || !is_numeric($end))
			return 'DB: firstId and lastId have to be integer values';		

		$data = array(
			'startid'	=> $start,
			'endid' => $end,
			'crdate' => $this->getTime(),
			'tstamp' => $this->getTime(),
			'pid' => intval($this->extCONF['pids.']['sessions']),
			'name' => $name,
			'description' => $description,
			'hidden' => $hidden,
			'room' => $roomId
		);
		$res = $this->db->exec_INSERTquery('tx_vjchat_session', $data);		
		
		return 'makesession success';
	}
	
	function getFeUser($uid) {
	
		if(!$uid)
			return array();
			
		$uid = intval($uid);
	
		$res = $this->db->exec_SELECTquery('*','fe_users', ' uid = '.$uid);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return $this->db->sql_fetch_assoc($res);
	}
	
	function getFeUserByName($username) {
		if(!$username)
			return NULL;
	
		$this->setDebug(true);
		$res = $this->db->exec_SELECTquery('*','fe_users', ' username = '.$this->db->fullQuoteStr($username,'fe_users'));
	
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		
		return $this->db->sql_fetch_assoc($res);
	}
	
	function deleteEntry($entryId) {
		$entryId = intval($entryId);
		$res = $this->db->exec_UPDATEquery('tx_vjchat_entry', ' uid = '.$entryId, array('deleted' => 1));
		return true;
	}
	
	function deleteEntries($roomId, $time) {
		$roomId = intval($roomId);
		$time = intval($time);
		//print ($this->getTime() - $time);
		$res = $this->db->exec_DELETEquery('tx_vjchat_entry', 'tstamp < '.($this->getTime() - $time));

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return $this->db->sql_affected_rows();
	}
	
	function getEntry($entryId) {
		
		$entryId = intval($entryId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_entry', ' uid = '.$entryId);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if($row = $this->db->sql_fetch_assoc($res)) {

			$entry = t3lib_div::makeInstance('tx_vjchat_entry');
			$entry->fromArray($row);

			return $entry;
		}
		
		return NULL;
	}
	
	function commitMessage($entryId) {
	
		$entryId = intval($entryId);
	
			// TODO: this is in some way critical 
		$res = $this->db->exec_SELECTquery('max(uid)','tx_vjchat_entry', '');
		$row = $this->db->sql_fetch_row($res);
		$newId = $row[0]+1;
			// any operations here? needs to be atomic!

			// LAST_INSERT_ID() does not work
		$res = $this->db->exec_UPDATEquery('tx_vjchat_entry', ' uid = '.$entryId.' AND hidden = 1', array('uid' => $newId, 'crdate' => $this->getTime(), 'tstamp' => $this->getTime(),'hidden' => 0));

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return $this->db->sql_affected_rows();
	
	}

	function enableFields($table, $show_hidden = 0)	{
		$hidden = $show_hidden ? '' : 'AND hidden = 0';
		return ' AND deleted = 0 '.$hidden.' AND (starttime<='.time().') AND (endtime=0 OR endtime>'.time().')';
	}
	
	function makeExpert($room, $userId) {
	
		$userId = intval($userId);
	
		$list = $room->experts;
		$newList =  $list.','.$userId;
		$newList = t3lib_div::uniqueList($newList);

		if($newList != $list) {
			$res = $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.(intval($room->uid)), array('experts' => $newList));

			if(!$res)
				print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

			return $this->db->sql_affected_rows();
		}
		return 0;
	}

	function makeUser($room, $userId) {
		
		$userId = intval($userId);
		
		$list = $room->experts;
		$newList = t3lib_div::rmFromList($userId, $list);

		if($newList != $list) {
			$res =  $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.(intval($room->uid)), array('experts' => $newList));
			return $this->db->sql_affected_rows();
		}
		return 0;

	}
	
	
	function addMemberToRoom($room, $userId) {
	
		$userId = intval($userId);
	
		$list = $room->members;
		$newList =  $list.','.$userId;
		$newList = t3lib_div::uniqueList($newList);

		if($newList != $list) {
			$res = $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.(intval($room->uid)), array('members' => $newList));

			if(!$res)
				print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

			return $this->db->sql_affected_rows();
		}
		return 0;
	}	
	
	/** Removes all entries that are not in a session and all entries that are marked hidden or deleted 
	  * @param Room 
	  * @param Time Delete only entries that are older than time
	  * @return amount of deleted rows
	  */
	
	function cleanUpRoom($room, $time = 0) {
	
		// get sessions of all rooms
		$sessions = $this->getSessions();
		$time = intval($time);
		
		// get entries of all sessions
		$entries = array();
		foreach($sessions as $session) {
			$sessionEntries = $this->getEntriesOfSession($session);
			foreach($sessionEntries as $sessionEntry)
				$entries[] = $sessionEntry->uid;
		}

		// this is a list of entry uids that should not be deleted
		if(count($entries))
			$list = '('.implode(',',$entries).')';
		else
			$list = '(0)';
		
		$res = $this->db->exec_DELETEquery('tx_vjchat_entry', '((uid NOT IN '.$list.') OR (hidden = 1) OR (deleted = 1)) AND room = '.(intval($room->uid)).' AND tstamp < '.($this->getTime() - $time));

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return $this->db->sql_affected_rows();
		
	}
	
	function cleanUpAllRooms($time = 0) {
		$rooms = $this->getRooms();
		
		$result = 0;
		
		foreach($rooms as $room) {
			$result = $result + $this->cleanUpRoom($room, $time);
		}
		return $result;
	}
	
	function getUserStatus($roomId, $userId, $statusLabel) {
		
		$roomId = intval($roomId);
		$userId = intval($userId);
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId);
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		$row = $this->db->sql_fetch_assoc($res);

		return $row[$statusLabel];
	
	}	
	

	function setUserStatus($room, $user, $statusLabel) {
		$users = $this->getFeUsersOfRoom($room, true);
		
		$newStatus = array();
		
		switch($statusLabel) {
			case 'hidden': 
				$status = $this->getUserStatus($room->uid, $user['uid'], 'invisible');

				$newStatus['invisible'] = $status ? '0' : '1';
		}

		$res =  $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.(intval($room->uid)).' AND uid_foreign = '.(intval($user['uid'])), $newStatus);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		return $this->db->sql_affected_rows();
		
	}
	
	function getRoomStatus($roomId, $statusLabel) {

		$roomId = intval($roomId);
	
		$res = $this->db->exec_SELECTquery('*','tx_vjchat_room', 'uid = '.$roomId);
		
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);
		
		$row = $this->db->sql_fetch_assoc($res);

		return $row[$statusLabel];
	
	}	
	

	function setRoomStatus($room, $statusLabel) {
		$users = $this->getFeUsersOfRoom($room, true);
		
		$newStatus = array();
		
		switch($statusLabel) {
			case 'hidden': 
				$status = $this->getRoomStatus($room->uid, 'hidden');
				$newStatus['hidden'] = $status ? '0' : '1';
				break;
			case 'private': 
				$status = $this->getRoomStatus($room->uid, 'private');
				$newStatus['private'] = $status ? '0' : '1';
				break;
		}

		$res =  $this->db->exec_UPDATEquery('tx_vjchat_room', 'uid = '.(intval($room->uid)), $newStatus);

		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);

		if($this->db->sql_affected_rows())
			return $newStatus[$statusLabel] ? 'on' : 'off';
			
		return 0;
		
	}
		
	function setMessageStyle($user, $style) {
		$res = $this->db->exec_UPDATEquery('fe_users', 'uid = '.(intval($user['uid'])), array('tx_vjchat_chatstyle' => $style));
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_affected_rows();
	}
	
	
	function setUserlistSnippet($roomId, $userId, $snippet) {
		$roomId = intval($roomId);
		$userId = intval($userId);		
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId, array('userlistsnippet' => $snippet));
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_affected_rows();
	}

	function setTooltipSnippet($roomId, $userId, $snippet) {
		$roomId = intval($roomId);
		$userId = intval($userId);		
		$res = $this->db->exec_UPDATEquery('tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId, array('tooltipsnippet' => $snippet));
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_affected_rows();
	}
	
	function getSnippets($roomId, $userId) {
		$roomId = intval($roomId);
		$userId = intval($userId);		
		$res = $this->db->exec_SELECTquery('userlistsnippet,tooltipsnippet','tx_vjchat_room_feusers_mm', 'uid_local = '.$roomId.' AND uid_foreign = '.$userId);
		if(!$res)
			print '#'.__LINE__.' - '.($this->db->debug_lastBuiltQuery);		
		return $this->db->sql_fetch_assoc($res);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_db.php']);
}


?>