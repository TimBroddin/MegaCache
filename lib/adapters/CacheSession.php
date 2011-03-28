<?php
/**
 * CacheSession file
 * Contains the CacheSession class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */

/**
 * CacheSession class.
 *
 * Saves the data in the user's $_SESSION variable
 * Not much use, except if nothing else is available
 * Persistent across pages, not persistent between users
 *
 * @author Tim Broddin <tim@brodd.in> 
 * @package MegaCache 
 * @extends BaseCache
 */
class CacheSession extends BaseCache  {

	/**
	 * __construct function.
	 * 
	 * No configuration options available
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		if(!session_id()) {
			session_start();
			$_SESSION['cache'] = array();
		}
		$this->clearTimedOut();
		parent::__construct($config);
	}
	
	/**
	 * get function.
	 * 
	 * gets a variable
	 *
	 * @access public
	 * @param string $varName
	 * @return mixed
	 */
	public function get($varName) {
		parent::get($varName);
		if(array_key_exists($varName, $_SESSION['cache'])) {
			if($_SESSION[$varName]['timeout'] > time()) {
				$this->sessionStats['hits']++;
				return $_SESSION[$varName]['value'];
			} else {
				$this->sessionStats['misses']++;
				return false;
			}
		} else {
			$this->sessionStats['misses']++;
			return false;
		}
	}
	
	/**
	 * set function.
	 * 
	 * sets a variable
	 *
	 * @access public
	 * @param string $varName
	 * @param mixed $value
	 * @param int $timeout. (default: 0)
	 * @return bool
	 */
	public function set($varName, $value, $timeout=0) {
		parent::set($varName, $value, $timeout);
		$timeout = ($timeout) ? intval(time() + $timeout) : time() + 60*60*24*365*10; // if no timeout is given set 10 years	
		$_SESSION['cache'][$varName] = array('value' => $value, 'timeout' => $timeout);
		return true;
	}
	
	/**
	 * delete function.
	 * 
	 * deletes a variable
	 *
	 * @access public
	 * @param mixed $varName
	 * @return bool
	 */
	public function delete($varName) {
		parent::delete($varName);
		if(array_key_exists($varName, $_SESSION)) {
			unset($_SESSION['cache'][$varName]);
		}
		return true;
	}
	
	/**
	 * clearTimedOut function.
	 * 
	 * @access private
	 * @return void
	 */
	private function clearTimedOut() {
		$now = time();
		foreach($_SESSION['cache'] as $varName => $value) {
			if($value['timeout'] < $now) {
				unset($_SESSION['cache'][$varName]);
			}
		}
	}
	
	/**
	 * __destruct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->saveStore();
		$this->globalStats(true);
	}	
	
}

