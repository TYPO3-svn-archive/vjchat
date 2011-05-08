<?php


$timer = new Timer();
$timer->enabled = ($_GET['d'] == 'timer' || $_POST['d'] == 'timer');
$timer->start('all');

	// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) 	die ('Could not access this script directly!');

	// Initialize FE user object:
$feUserObj = tslib_eidtools::initFeUser();

	// Connect to database:
tslib_eidtools::connectDB();

$charset = $_GET['charset'] ? $_GET['charset'] : 'iso-8859-1';
$charset = $TYPO3_CONF_VARS['BE']['forceCharset'] ? $TYPO3_CONF_VARS['BE']['forceCharset'] : $charset;

##################
## HEADER

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
//header("Cache-Control: post-check=0, pre-check=0", false);
header('Content-type: text/plain; charset='.$charset);

################
## CHAT
require_once(t3lib_extMgm::siteRelPath('vjchat').'pi1/class.tx_vjchat_chat.php');

$timer->start('chat');
$chat = t3lib_div::makeInstance('tx_vjchat_chat');
$chat->init($feUserObj, $charset);
print $chat->perform();


$timer->stop('chat');
$timer->stop('all');

if($_GET['d'] == 'timer' || $_POST['d'] == 'timer') {
	print '<debug>'.$timer->output().'</debug>';
}


class Timer {

	var	$timers = array();
	var $dec = 1;
	var $precision = 4;
	var $enabled = true;


	function start($label) {

		if(!$this->enabled)
			return;
			
		$this->timers[$label]['start'] = microtime();
		
		$backtrace = debug_backtrace();
		
		$this->timers[$label]['line'] = $backtrace[0]['line'] ;
		while(strlen($this->timers[$label]['line']) < 4)
			$this->timers[$label]['line'] = '0'.$this->timers[$label]['line'];
	}
	
	function stop($label) {
		$this->timers[$label]['end'] = microtime();	
	}
	
	function output() {
		$out = '';
		//var_dump($this->timers);
		foreach($this->timers as $key => $timer) {
			$time = ($this->getMicrotime($timer['end']) -  $this->getMicrotime($timer['start']));
			//$out .= '<tr><td width="150" style="font-weight:bold; font-size: 8pt; font-family: Courier New;border-bottom: 1px solid gray;">'.$timer['line'].'</td><td>'.$key.'</td><td style="font-size: 8pt; font-family: Courier New;border-bottom: 1px solid gray;">'.$this->format($time).'</td></tr>' ;
			$out .= '&lt;stat label="'.$key.'" time="'.$this->format($time).'" /&gt;';
		}
		//return '<table style="border-top: 1px solid black;border-bottom: 1px solid black;" cellpadding="0" cellspacing="0">'.$out.'</table>';
		
	
		return '&lt;stats&gt;'.$out.'&lt;/stats&gt;<br>';
	}
	
	function format($time) {
		$timeArray = explode('.',$time);

		while(strlen($timeArray[0]) < $this->dec)
			$timeArray[0] = '0'.$timeArray[0];

		while(strlen($timeArray[1]) < $this->precision)
			$timeArray[1] = $timeArray[1].'0';
		
		$timeArray[1] = substr($timeArray[1], 0, $this->precision);
		
		return implode('.', $timeArray);
		
	}

	function getMicrotime($microtime = NULL) {
	    if(!$microtime)
    	    $microtime = microtime();
	   list($usec, $sec) = explode(" ", $microtime);
	   return ((float)$usec + (float)$sec);
	}	
}

?>
