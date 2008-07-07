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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_users', '', 'username LIMIT 0,1');		
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		var_dump($items);
		$items['items'] = array();
		foreach($row as $key => $value) {
			$items['items'][] = array($key, $key);
		}
		
		return $items;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_itemsProcFunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vjchat/pi1/class.tx_vjchat_itemsProcFunc.php']);
}

?>