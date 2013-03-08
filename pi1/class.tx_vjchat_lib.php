<?php

require_once(t3lib_extMgm::siteRelPath('vjchat').'pi1/class.JSON.php');

class tx_vjchat_lib {

	function getUserNamesFieldGlue() {
		return ': ';
	}

	function checkAccessToRoom($room, $user) {

		if(!$user)
			return false;

		//is banned?
		//if(tx_vjchat_lib::isBanned($room, $user['uid']))
		//	return true;

		//t3lib_div::debug($room);

		// Are there any restrictions? if not return true
		if((!$room->groupaccess) && (!$room->fe_group))
			return true;

		// is superuser?
		if(tx_vjchat_lib::isSuperuser($room, $user))
			return true;

		// is moderator?
		if(tx_vjchat_lib::isModerator($room, $user['uid']))
			return true;
		

		// is closed?
		if($room->closed && !tx_vjchat_lib::isModerator($room, $user['uid']))
			return false;

		// if no usergroup is assigned to the room - allow all users
		if(!$room->groupaccess)			
			return true;
		
		// is user in usergroup?
		$groupsOfUser = t3lib_div::intExplode(',', $user['usergroup']);
		foreach($groupsOfUser as $g) {

			if(t3lib_div::inList($room->groupaccess, $g))
				return true;
			if($g === $room->fe_group)
				return true;
		}
		
	
		// restricted
		return false;
	}
	
	function isSuperuser($room, $user) {
	
		if(!$user)
			return false;

		// is user in usergroup of superusers?
		$groupsOfUser = t3lib_div::intExplode(',', $user['usergroup']);
		foreach($groupsOfUser as $g) {
			if(t3lib_div::inList($room->superusergroup, $g)) {
				return true;
			}
		}

		return false;
				
	}
	
	function isModerator($room, $userid) {

		if(!$userid)
			return false;

		// is moderator?
		if(t3lib_div::inList($room->moderators, $userid))
			return true;
			
		return false;
	}

	function isBanned($room, $userid) {

		if(!$userid)
			return false;

		// is banned?
		if(t3lib_div::inList($room->bannedusers, $userid)) {
			return true;
		}
			
		return false;
	}

	function isMember($room, $userid) {

		if(!$userid)
			return false;

			// is owner?
		if(tx_vjchat_lib::isOwner($room, $userid))
			return true;

			// is member?
		if(t3lib_div::inList($room->members, $userid)) {
			return true;
		}			
			
		return false;
	}

	function isExpert($room, $userid) {

		if(!$userid)
			return false;

		// is expert?
		if(t3lib_div::inList($room->experts, $userid))
			return true;
			
		return false;
	}
	
	function isOwner($room, $userid) {
		return ($room->owner == $userid);
	}

	function getUserTypeString($room, $user) {
		if(tx_vjchat_lib::isSuperuser($room, $user))
			return 'superuser';
		if(tx_vjchat_lib::isOwner($room, $user['uid']))
			return 'owner';
		if(tx_vjchat_lib::isModerator($room, $user['uid']))
			return 'moderator';
		if(tx_vjchat_lib::isExpert($room, $user['uid']))
			return 'expert';
		return 'user';
	}

	/**
	  * Returns the chat user name that is the content of the field defined the room's flexform
	  */
	function getChatUserName($room, $user) {
		$chatUsername = '';
		
		if(tx_vjchat_lib::isSuperuser($room, $user['uid']))
			$chatUsername = $user[$room->chatUserNameFieldSuperusers];
			
		if(tx_vjchat_lib::isModerator($room, $user['uid']))
			$chatUsername = $user[$room->chatUserNameFieldModerators];
		
		if(tx_vjchat_lib::isExpert($room, $user['uid']))
			$chatUsername = $user[$room->chatUserNameFieldExperts];
		
		if($chatUsername == '')
			$chatUsername = $user[$room->chatUserNameFieldUsers];
		
		return $chatUsername == '' ? $user['username'] : $chatUsername;

	}

	
	/**
	 * This function encodes all occuring usernames (depending on the assigned fe_user field) 
	 * in content based on a user base. If the user base is empty all members (of all usertype) are used.
	 */
	function findAndEncodeChatUserNames($room, $content, $userBase) {
		if(!$room)
			return $content;
		foreach($userBase as $user) {
			$currentUserName = tx_vjchat_lib::getChatUserName($room, $user);
			$encodedUser = tx_vjchat_lib::encodeUsername($currentUserName);
			// replace the user name by the encoded one
			$content = str_replace($currentUserName, $encodedUser, $content);
		}
		return $content;
	}
	
	/**
	 * This function encodes all occuring usernames (depending on the assigned fe_user field) 
	 * in content based on a user base. If the user base is empty all members (of all usertype) are used.
	 */
	function findAndDecodeChatUserNames($room, $content, $userBase) {
		if(!$room)
			return $content;
		foreach($userBase as $user) {
			$currentUserName = tx_vjchat_lib::getChatUserName($room, $user);
			$encodedUser = tx_vjchat_lib::encodeUsername($currentUserName);
				// replace the encoded user name by the non-encoded one
			$content = str_replace($encodedUser, $currentUserName, $content);
		}
		return $content;
	}	
	function encodeUsername($username) {
		return urlencode($username);
	}
	
	function decodeUsername($chatUsername = '') {
		return urldecode($chatUsername);
	}	

	function getUserByChatUserName($room, $chatUsername, $userBase) {
		// in principle, it is possible that this functions returns more than one user, in the case of a not unique ChatUserName
		// all calling functions should be aware of this and handle this
		$users = array();
		foreach($userBase as $user) {
			if($chatUsername == tx_vjchat_lib::getChatUserName($room, $user))
				$users[] = $user;
		}
		return $users;			
	}	

	
	function getUsernames($feusers, $userfieldname = 'username', $glue = ',&nbsp;', $cObj = null, $stdWrap = null) {
		$userNames = array();
		foreach($feusers as $user) {
		
			$userName = !empty($user[$userfieldname]) ? $user[$userfieldname] : $user['username'];
			
			if(empty($userName))
				continue;
			
			if($cObj && $stdWrap) {
				$cObj->data = array_merge($cObj->data, $user);
				$userNames[] = $cObj->stdWrap($userName, $stdWrap);
			}
			else
				$userNames[] = $userName;
				
		}
		return implode($glue, $userNames);
	}	
	
	function isSystem($userId) {
		// if no user isset it is a system message
		return ($userId ? false : true);
	}
	
	function get_links($body) {
	   //Pattern building across multiple lines to avoid page distortion.
	   $pattern = "/((http\:\/\/)?www\.[a-z0-9\.\:\-\_\/\~\@\%]*)/i";
	   //End pattern building.
	   preg_match_all ($pattern, $body, $matches);
	   return (is_array($matches)) ? $matches:FALSE;
	}	
	
	function formatMessage($text, $enableEmoticons = true, $emoticons_path = 'typo3conf/ext/vjchat/pi1/emoticons/') {
	
		// replace line breaks with <br>
		$text = str_replace(chr(10), '<br />', $text);		
	
		// Check if emoticons are disabled
		if ($enableEmoticons) {
			$emoticons = array();
			$searchEmoticons = array();
			$replaceEmoticons = array();

			// same are used in chc_forum
			$emoticons = tx_vjchat_lib::getEmoticons();

			$emicoPath = $emoticons_path;
	
			// Replace all emoticon codes with images
			foreach($emoticons as $emoticon => $file) {
				$img = '<img src="'.$emicoPath.$file.'" alt="'.tx_vjchat_lib::unicode_encode($emoticon).'" title="'.tx_vjchat_lib::unicode_encode($emoticon).'" />';
				$text = str_replace($emoticon, $img, $text);
			}
			
			// temporary removed since xss risk
			//$text = preg_replace('/\[email\](.*)\[\/email\]/i', '<span class="tx-vjchat-email"><a href="mailto:\1">\1</a></span>', $text);
			//$text = preg_replace('/\[url\](.*)\[\/url\]/i', '<span class="tx-vjchat-url"><a href="\1" target="_blank">\1</a></span>', $text);			

			$text = preg_replace('/\[b\](.*?)\[\/b\]/i', '<span class="tx-vjchat-bold">\1</span>', $text);
			$text = preg_replace('/\[u\](.*?)\[\/u\]/i', '<span class="tx-vjchat-underlined">\1</span>', $text);
			$text = preg_replace('/\[i\](.*?)\[\/i\]/i', '<span class="tx-vjchat-italic">\1</span>', $text);
			$text = preg_replace('/\[s\](.*?)\[\/s\]/i', '<span class="tx-vjchat-stroke">\1</span>', $text);

			$text = preg_replace('/(\*.*?\*)/i', '<span class="tx-vjchat-bold">\1</span>', $text);
			$text = preg_replace('/(\_.*?\_)/i', '<span class="tx-vjchat-underlined">\1</span>', $text);


		}
	
		return $text;
		
	}
	
	function getEmoticons($getAll = true) {
			$theValue = array(
								':arrow:' => 'arrow.gif',
								 ':badgrin:' => 'badgrin.gif',
								 ':D' => 'biggrin.gif',
								 ':?' => 'confused.gif',
								 '8-)' => 'cool.gif',
								 ':(' => 'cry.gif',
								 ':doubt:' => 'doubt.gif',
								 ':evil:' => 'evil.gif',
								 ':!:' => 'exclaim.gif',
								 ':idea:' => 'idea.gif',
								 ':lol:' => 'lol.gif',
								 ':mad:' => 'mad.gif',
								 ':neutral:' => 'neutral.gif',
								 ':question:' => 'question.gif',
								 ':razz:' => 'razz.gif',
								 ':oops:' => 'redface.gif',
								 ':roll:' => 'rolleyes.gif',
								 ':-(' => 'sad.gif',								 
								 ':shock:' => 'shock.gif',
								 ':)' => 'smile.gif',
								 ':-)' => 'smile.gif',
								 ';)' => 'smile.gif',
								 ';-)' => 'smile.gif',								 
								 ':-o' => 'surprised.gif',
								 ':wink:' => 'wink.gif',
								 );
		if(!$getAll)	
			return $theValue;
		
		foreach($theValue as $key => $value) {
			if(preg_match('/^\:(.*)\:$/i', $key, $matches)) {
				$theValue['*'.$matches[1].'*'] = $value;
			}
		}

		return $theValue;
	}
	
	function getEmoticonsForChatRoom($emoticons_path = 'typo3conf/ext/vjchat/pi1/emoticons/') {
		$emoticons = tx_vjchat_lib::getEmoticons(false);

		$out = "";
		$files = array();
		foreach($emoticons as $code => $file) {

			// output file only once
			if(in_array($file, $files))
				continue;

			$files[] = $file;
			$out .= '<img onClick="setValueToInput(\''.$code.'\');" src="'.$emoticons_path.$file.'" alt="'.tx_vjchat_lib::unicode_encode($code).'" title="'.tx_vjchat_lib::unicode_encode($code).'" />';

		}
		
		//$out = '<div class="tx-vjchat-emoticons">'.$out.'</div>';
		
		return $out;
	}
	
	function unicode_encode($string) {
		$chars = array(
			' ' => '&#32;',
			'!'	=> '&#33;',
			'\"'=> '&#34;',
			'\#'=> '&#35;',
			'\$'=> '&#36;',
			'\%'=> '&#37;',
			'\&'=> '&#38;',
			'\''=> '&#39;',
			'('	=> '&#40;',
			')'	=> '&#41;',
			'*'	=> '&#42;',
			'+'	=> '&#43;',
			','	=> '&#44;',
			'-'	=> '&#45;',
			'.'	=> '&#46;',
			'\/'=> '&#47;',
			'0'	=> '&#48;',
			'1'	=> '&#49;',
			'2'	=> '&#50;',
			'3'	=> '&#51;',
			'4'	=> '&#52;',
			'5'	=> '&#53;',
			'6'	=> '&#54;',
			'7'	=> '&#55;',
			'8'	=> '&#56;',
			'9'	=> '&#57;',
			':'	=> '&#58;',
			';'	=> '&#59;',
			'<'	=> '&#60;',
			'='	=> '&#61;',
			'>'	=> '&#62;',
			'?'	=> '&#63;',
			'@'	=> '&#64;',
			'A'	=> '&#65;',
			'B'	=> '&#66;',
			'C'	=> '&#67;',
			'D'	=> '&#68;',
			'E'	=> '&#69;',
			'F'	=> '&#70;',
			'G'	=> '&#71;',
			'H'	=> '&#72;',
			'I'	=> '&#73;',
			'J'	=> '&#74;',
			'K'	=> '&#75;',
			'L'	=> '&#76;',
			'M'	=> '&#77;',
			'N'	=> '&#78;',
			'O'	=> '&#79;',
			'P'	=> '&#80;',
			'Q'	=> '&#81;',
			'R'	=> '&#82;',
			'S'	=> '&#83;',
			'T'	=> '&#84;',
			'U'	=> '&#85;',
			'V'	=> '&#86;',
			'W'	=> '&#87;',
			'X'	=> '&#88;',
			'Y'	=> '&#89;',
			'Z'	=> '&#90;',
			'['	=> '&#91;',
			'\\'=> '&#92;',
			']'	=> '&#93;',
			'^'	=> '&#94;',
			'_'	=> '&#95;',
			'\`'=> '&#96;',
			'a'	=> '&#97;',
			'b'	=> '&#98;',
			'c'	=> '&#99;',
			'd'	=> '&#100;',
			'e'	=> '&#101;',
			'f'	=> '&#102;',
			'g'	=> '&#103;',
			'h'	=> '&#104;',
			'i'	=> '&#105;',
			'j'	=> '&#106;',
			'k'	=> '&#107;',
			'l'	=> '&#108;',
			'm'	=> '&#109;',
			'n'	=> '&#110;',
			'o'	=> '&#111;',
			'p'	=> '&#112;',
			'q'	=> '&#113;',
			'r'	=> '&#114;',
			's'	=> '&#115;',
			't'	=> '&#116;',
			'u'	=> '&#117;',
			'v'	=> '&#118;',
			'w'	=> '&#119;',
			'x'	=> '&#120;',
			'y'	=> '&#121;',
			'z'	=> '&#122;',
			'{'	=> '&#123;',
			'|'	=> '&#124;',
			'}'	=> '&#125;',
			'~'	=> '&#126;',
		);
		
		$theValue = '';
		for($i = 0; $i < strlen($string);$i++) {
			$theValue .= $chars[$string[$i]] ? $chars[$string[$i]] : $string[$i];
		}
		
		return $theValue;
		
	}
	
	function getExtConf() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vjchat']);	
	}
	
	function trimImplode($glue, $array) {
		foreach($array as $key => $value) {
			if(!$value)
				unset($array[$key]);
		}
		return implode($glue, $array);
	}
	
	/**
	* RFC1738 compliant replacement to PHP's rawurldecode - which actually works with unicode (using utf-8 encoding)
	* @author Ronen Botzer
	* @param $source [STRING]
	* @return unicode safe rawurldecoded string [STRING]
	* @access public
	*/
	function utf8RawUrlDecode ($source, $utf8) {
		$decodedStr = '';
		$pos = 0;
		$len = strlen ($source);
	
		while ($pos < $len) {
			$charAt = substr ($source, $pos, 1);
			if ($charAt == '%') {
				$pos++;
				$charAt = substr ($source, $pos, 1);
				if ($charAt == 'u') {
					// we got a unicode character
					$pos++;
					$unicodeHexVal = substr ($source, $pos, 4);
					$unicode = hexdec ($unicodeHexVal);
					$entity = "&#". $unicode . ';';
					$decodedStr .= utf8_encode ($entity);
					$pos += 4;
				}
				else {
					// we have an escaped ascii character
					$hexVal = substr ($source, $pos, 2);
					if($utf8)
						$decodedStr .= utf8_encode(chr (hexdec ($hexVal)));
					else
						$decodedStr .= chr (hexdec ($hexVal));
					$pos += 2;
				}
			}
			else {
				$decodedStr .= $charAt;
				$pos++;
			}
		}
	
		return $decodedStr;
	} 
	
	function json_decode($content) {
		
		if ($assoc) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		}
		else {
			$json = new Services_JSON;
		}
		return $json->decode($content);
	}

	function json_encode($content, $root = NULL) {	
		if($root)
			$content = array($root => $content);
	
		$json = new Services_JSON;
		return $json->encode($content);
	}
	
	
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_lib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_lib.php']);
}



?>