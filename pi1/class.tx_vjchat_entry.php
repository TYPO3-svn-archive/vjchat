<?php

//require_once(PATH_t3lib.'class.t3lib_div.php');

class tx_vjchat_entry {

	var $uid;
	var $entry;
	var $crdate;
	var $tstamp;	
	var $feuser;
	var $tofeuserid;	
	var $room;
	var $hidden;
	var $deleted;
	var $style;


	function tx_vjchat_entry() {
	
	}

	function fromArray($array) {
		$this->uid = $array['uid'];
		$this->crdate = $array['crdate'];
		$this->tstamp = $array['tstamp'];		
		$this->entry = $array['entry'];
		$this->feuser = $array['feuser'];
		$this->tofeuserid = $array['tofeuser'];
		$this->room = $array['room'];
		$this->hidden = $array['hidden'];
		$this->deleted = $array['deleted'];
		$this->style = $array['style'];
		
	}

	function toArray() {

		$theValue = array(
			'uid' => $this->uid,
			'crdate' => $this->crdate,
			'tstamp' => $this->tstamp,			
			'entry' => $this->entry,
			'feuser' => $this->feuser,
			'tofeuser' => $this->tofeuserid,
			'room' => $this->room,						
			'hidden' => $this->hidden,						
			'deleted' => $this->deleted,
			'style' => $this->style,
								
		);

		return $theValue;
	}
	
	function isPrivate() {
		return ($this->tofeuserid > 0);
	}

	function toString() {
		
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_entry.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_entry.php']);
}


?>