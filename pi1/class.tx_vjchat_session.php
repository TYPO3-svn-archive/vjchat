<?php

//require_once(PATH_t3lib.'class.t3lib_div.php');

class tx_vjchat_session {

	var $uid;
	var $name;
	var $description;
	var $public;
	var $room;
	var $startid;
	var $endid;

	function tx_vjchat_session() {
	
	}
	

	function fromArray($array) {
		$this->uid = intval($array['uid']);
		$this->name = $array['name'];
		$this->description = $array['description'];
		$this->public = $array['hidden'];
		$this->room = $array['room'];
		$this->startid = intval($array['startid']);
		$this->endid = intval($array['endid']);

	}

	function toArray() {

		$theValue = array(
			'uid' => intval($this->uid),
			'name' => $this->name,
			'description' => $this->description,
			'hidden' => $this->public,
			'room' => $this->room,
			'startid' => intval($this->startid),
			'endid' => intval($this->endid),
		);

		return $theValue;
	}

/*	function getModeratorIDs() {
		$moderators = array();

		foreach ($this->moderators as $moderator)
			$moderators[] = $moderator['uid'];

		return implode(',',$moderators);
				
	}	
*/
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_session.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_session.php']);
}

?>