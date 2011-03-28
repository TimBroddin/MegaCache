<?php
/**
 * CacheSingleFile file
 * Contains the CacheSingleFile class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */

/**
 * CacheSingleFile class.
 * 
 * Stores the cache data in a single file. Persistent accross requests and users. 
 *
 * Since there is no locking mechanism this is not thread safe (two users who access your site at the same time will overwrite the same data)
 *
 * Requires two configuration options: cachePath and cacheName - see below
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class CacheSingleFile extends BaseCache  {
	private $dataStore; // array[varname] = {timeout, value}
	private $path;

	/**
	 * __construct function.
	 * 
	 * the configuration array passed must contain these properties:
	 *	- cachePath: the path where to store the database (path to the directory without trailing slash)
	 *	- cacheName: the name of the file where to save to
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		$this->path = realpath($config['cachePath']) . "/{$config['cacheName']}.txt";
		if(!file_exists($this->path)) {
			touch($this->path);
			chmod($this->path, 0666);
		}
		// double check
		if(!file_exists($this->path)) {
			throw new Exception('Cache file does not exists. Make sure ' . realpath($config['cachePath']) . ' is writable');
		}
		$this->dataStore = unserialize(file_get_contents($this->path));
		// new
		if(!$this->dataStore) {
			$this->dataStore = array();
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
		if(array_key_exists($varName, $this->dataStore)) {
			if($this->dataStore[$varName]['timeout'] > time()) {
				$this->sessionStats['hits']++;
				return $this->dataStore[$varName]['value'];
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
		$this->dataStore[$varName] = array('value' => $value, 'timeout' => $timeout);
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
		if(array_key_exists($varName, $this->dataStore)) {
			unset($this->dataStore[$varName]);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * clearTimedOut function.
	 * 
	 * @access private
	 * @return void
	 */
	private function clearTimedOut() {
		$now = time();
		foreach($this->dataStore as $varName => $value) {
			if($value['timeout'] < $now) {
				unset($this->dataStore[$varName]);
			}
		}
	}
	
	/**
	 * flush function.
	 *
	 * empty out array
	 * 
	 * @access public
	 * @return void
	 */
	public function flush() {
		parent::flush();
		$this->dataStore = array();
	}
	
	public function __destruct() {
		$this->saveStore();
		$this->globalStats(true);
		file_put_contents($this->path, serialize($this->dataStore));
	}	
	
}

