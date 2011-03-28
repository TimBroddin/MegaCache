<?php
/**
 * CacheMemcached file
 * Contains the CacheMemcached class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */

/**
 * CacheMemcached class.
 *
 * adapter for the memcached extension
 * if you use the memcache extension instead please use that adapter
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class CacheMemcached extends BaseCache  {
	private $memcached;
	private $cacheName;

	/**
	 * __construct function.
	 * 
	 * Two configuration options:
	 *  servers: an array containing which server(s) to use
	 *  cacheName: a name for this cache (to make sure you don't interfere with other caches)
	 *
	 * Example: CacheFactory::factory('memcached', array('servers' => array(array('host' => 'localhost')), 'cacheName' => 'mycache'))
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */	
	public function __construct($config) {
		if(!extension_loaded('memcached')) {
			throw new Exception('Please enable memcached');
		}
		// create object
		$this->memcached = new Memcache();
		// add servers
		foreach($config['servers'] as $server) {
			if(!isset($server['port'])) $server['port'] = 11211;
			$this->memcached->addServer($server['host'], $server['port']);
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
		if(!$result = $this->memcached->get($this->cacheName . $varName)) {
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
		$this->memcached->set($this->cacheName . $varName, $value, $timeout);
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
		return $this->memcached->delete($this->cacheName . $varName);
	}
	
	/**
	 * increment function.
	 * 
	 * override built in increment function because memcached has native implementation
	 *
	 * @access public
	 * @param string $varName
	 * @param int $with (default: 1)
	 * @return int
	 */	
	public function increment($varName, $with=1) {
		$this->sessionStats['increments']++;
		return $this->memcached->increment($this->cacheName . $varName, $with);
	}
	
	/**
	 * decrement function.
	 * 
	 * override built in decrement function because memcache has native implementation
	 *
	 * @access public
	 * @param string $varName
	 * @param int $with (default: 1)
	 * @return int
	 */
	public function decrement($varName, $with=1) {
		$this->sessionStats['decrements']++;
		return $this->memcached->decrement($this->cacheName . $varName, $with);
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

