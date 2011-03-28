<?php
/**
 * CacheAPC file
 * Contains the cacheAPC class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */


/**
 * CacheApc class.
 * 
 * Provides caching thanks to the built in APC cache methods
 * Requires the apc extension (which you should have anyway because it gives PHP a super speed boost)
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class cacheApc extends BaseCache  {
	private $cacheName;
	
	/**
	 * __construct function.
	 * 
	 * Takes one configuration option:
	 *  - cacheName: used to not interfere with other caches you might have
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		if(!extension_loaded('APC')) {
			throw new Exception('Please enable APC');
		}
		$this->cacheName = $config['cacheName'];
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
		if(!$result = apc_fetch($this->cacheName . $varName)) {
			$this->sessionStats['misses']++;
			return false;
		}
		$this->sessionStats['hits']++;
		return $result;
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
		apc_store($this->cacheName . $varName, $value, $timeout);
		return true;
	}
	
	/**
	 * delete function.
	 * 
	 * deletes a variable
	 *
	 * @access public
	 * @param string $varName
	 * @return bool
	 */
	public function delete($varName) {
		parent::delete($varName);
		return apc_delete($this->cacheName . $varName);
	}
	
	/**
	 * increment function.
	 * 
	 * override the increment function because apc has this built in
	 *
	 * @access public
	 * @param string $varName
	 * @param int $with (default: 1)
	 * @return int
	 */
	public function increment($varName, $with=1) {
		$this->sessionStats['increments']++;
		return apc_inc($this->cacheName . $varName, $with);
	}
	
	/**
	 * decrement function.
	 * 
	 * override the decrement function because apc has this built in
	 *
	 * @access public
	 * @param string $varName
	 * @param int $with (default: 1)
	 * @return int
	 */	
	public function decrement($varName, $with=1) {
		$this->sessionStats['decrements']++;
		return apc_dec($this->cacheName . $varName, $with);
	}
	
	/**
	 * flush function.
	 * 
	 * @access public
	 * @return void
	 */
	public function flush() {
		return apc_clear_cache('user');
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

