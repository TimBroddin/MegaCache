<?php
/**
 * CacheNull file
 * Contains the CacheNully class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */

/**
 * CacheNull class.
 * 
 * Provides a simple in-memory cache that is not persistent across requests or users
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class CacheNull extends BaseCache {
	private $dataStore;
	
	/**
	 * __construct function.
	 * 
	 * initializes the dataStore array
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		$this->dataStore = array();
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
		if(array_key_exists($varName, $this->dataStore)) {
			$this->sessionStats['hits']++;
			return $this->dataStore[$varName];
		} else {
			$this->sessionStats['misses']++;
			$this->sessionStats['gets']++;
		}
		return false; 
	}
	
	/**
	 * set function.
	 * 
	 * sets a variable 
	 * timeout is ignored within this adapter
	 *
	 * @access public
	 * @param string $varName
	 * @param mixed $value
	 * @param int $timeout. (default: 0)
	 * @return bool
	 */
	public function set($varName, $value, $timeout=0) { 
		$this->sessionStats['sets']++;
		$this->dataStore[$varName] = $value;
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
		$this->sessionStats['deletes']++;
		unset($this->dataStore[$varName]);
		return true;
	}
}