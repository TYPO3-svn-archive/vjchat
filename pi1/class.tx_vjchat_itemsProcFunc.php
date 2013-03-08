<?php

class tx_vjchat_itemsProcFunc {

	function user_vjchat_getFeUser(&$items) {

		var_dump($items);

		foreach($items['items'] as $key => $item) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_users', 'uid = '.$item[1], 'username');		
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$items['items'][$key][0] = $row['username'].' ('.$row['name'].')';
		}
		
		return $items;
		
	}	
	
	function user_vjchat_getFeUserColumns(&$items) {
		$res = $GLOBALS['TYPO3_DB']->sql_query("SHOW COLUMNS FROM fe_users");		
		$items['items'] = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if((!strstr($row['Type'],'text') && !strstr($row['Type'],'varchar')) || ($row['Field'] == 'password'))
				continue;
			$items['items'][] = array($row['Field'], $row['Field']);
			//$items['items'][] = $key;
		}
		return $items;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_itemsProcFunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_itemsProcFunc.php']);
}

?>