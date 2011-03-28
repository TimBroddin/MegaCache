<?php
/**
 * BaseCache file 
 *
 * contains the BaseCache class
 * @author Tim Broddin
 * @package MegaCache
 */

/**
 * BaseCache class.
 * 
 * contains everything that is not adapter specific
 * such as statistics, function caching, resource caching, ...
 *
 * @author Tim Broddin
 * @package MegaCache
 */
class BaseCache implements CacheInterface, ArrayAccess {
	protected $sessionStats;
	protected $store; // array containing keys for everything in cache - used for flushing
	private $currentFragment;
	
	/**
	 * __construct function.
	 * 
	 * initializes stats and store
	 *
	 * @access public
	 * @param mixed $configuration
	 * @return void
	 */
	public function __construct($configuration) {
		$this->sessionStats = array('hits' => 0, 'misses' => 0, 'sets' => 0, 'gets' => 0, 'deletes' => 0, 'increments' => 0, 'decrements' => 0);
		$this->store = $this->get('megacache-store');
		if(!is_array($this->store)) {
			$this->store = array();
		}
	}
	
	/**
	 * get function. will be called from the adapter itself
	 *
	 * Example:
	 *   $cache->get('myVar');
	 * 
	 * @access public
	 * @param string $varName
	 * @return mixed
	 */
	public function get($varName) { 
		$this->sessionStats['gets']++;
	}
	
	/**
	 * set function. will be called from the adapter itself
	 * 
	 * Example:
	 *	$cache->set('myVar', $value, 10);
	 *
	 * @access public
	 * @param string $varName
	 * @param mixed $value
	 * @param int $timeout. (default: 0)
	 * @return bool
	 */
	public function set($varName, $value, $timeout=0) {
		$this->sessionStats['sets']++;	
		$this->store[$varName] = true;
	}
	
	/**
	 * delete function. will be called from the adapter itself
	 * 
	 * Example:
	 *  $cache->delete('myVar');
	 *
	 * @access public
	 * @param mixed $varName
	 * @return bool
	 */
	public function delete($varName) {
		$this->sessionStats['deletes']++;
		unset($this->store[$varName]);	
	}
	

	/**
	 * increment function.
	 * 
	 * @access public
	 * @param string $varName
	 * @param int $with. (default: 1)
	 * @return bool
	 */
	public function increment($varName, $with=1) { 
		$this->sessionStats['increments']++;
		return $this->set($varName, $this->get($varName)+$with);
	}
	
	/**
	 * decrement function.
	 * 
	 * @access public
	 * @param string $varName
	 * @param int $with. (default: 1)
	 * @return bool
	 */
	public function decrement($varName, $with=1) { 
		$this->sessionStats['decrements']++;
		return $this->set($varName, $this->get($varName)-$with);
	}
	
	/**
	 * flush function.
	 * 
	 * flush the cache
	 *
	 * @access public
	 * @return void
	 */
	public function flush() {
		$protected = array('megacache-store', 'megacache-globalstats');
		foreach($this->store as $varName => $n) {
			if(!in_array($varName, $protected)) {
				$this->delete($varName);
			}
		}
		$this->store = array();
	}
	
	/**
	 * call function.
	 *
	 * cache a function call
	 *
	 * Example:
	 *  if you have a function called sum and you want to cache the results of it for 10 seconds:
	 *		$cache->call('sum', array(5,6), 10);
	 *		where you would normally write: sum(5,6);
	 *		
	 *	You can also call methods by passing an array as first argument: array($myObject, 'doSomething'); 
	 *
	 * @access public
	 * @param mixed $function
	 * @param array $arguments
	 * @param int $timeout. (default: 0)
	 * @return mixed
	 */
	public function call($function, $arguments, $timeout=0) {
		$signature = 'function-' . md5(serialize($function) . md5(serialize($arguments)));
		if(!$result = $this->get($signature)) {
			$result = call_user_func_array($function, $arguments);
			$this->set($signature, $result, $timeout);
		}
		return $result;
	}
	
	/**
	 * fetch function.
	 * 
	 * fetches a resource. can fetch anything file_get_contents can
	 *
	 * Example: $cache->fetch('http://www.google.com', 10);
	 *
	 * @access public
	 * @param string $resource
	 * @param int $timeout. (default: 0)
	 * @return mixed
	 */
	public function fetch($resource, $timeout=0)  {
		$signature = 'resource-' . md5($resource);
		if(!$result = $this->get($signature)) {
			$result = file_get_contents($resource);
			if($result) {
				$this->set($signature, $result, $timeout);
			}
		}
		return $result;
	}
	
	/**
	 * fragment function.
	 *
	 * outputs (echo) a fragment if it exists, otherwise starts output buffering until saveFragment is called
	 *
	 * Example:
	 *    if(!$cache->fragment('header')) {
	 * 		// do something difficult
	 *		// can be a lot of code
	 *		echo "result: " . $result;
	 *		$cache->saveFragment(10);
	 *	 }
	 *
	 *	This block of code will be cached for 10 seconds. Ideal for HTML Templating.
	 *
	 * @access public
	 * @param mixed $name
	 * @return bool
	 */
	public function fragment($name) {
		$result = $this->get('fragment-' . $name);
		if($result) {
			echo $result;
			return true;
		} else {
			// start output buffering
			$this->currentFragment = $name;
			ob_flush();
			ob_start();
			return false;
		}
	}
	
	/**
	 * saveFragment function.
	 * 
	 * see documenation above
	 *
	 * @access public
	 * @param int $timeout. (default: 0)
	 * @return bool
	 */
	public function saveFragment($timeout=0) {
		return $this->set('fragment-' . $this->currentFragment, ob_get_flush(), $timeout);
	} 
	
	/**
	 * getStats function.
	 * 
	 * returns an array containing statistics about this request (session) and globally (global)
	 *
	 * @access public
	 * @return array
	 */
	public function getStats() {
		return array('session' => $this->sessionStats, 'global' => $this->globalStats(false));
	}
	
	/**
	 * globalStats function.
	 * 
	 * @access protected
	 * @param bool $save. (default: true)
	 * @return void
	 */
	protected function globalStats($save=true) {
		$globalStats = $this->get('megacache-globalstats');
		if(!is_array($globalStats)) {
			$globalStats = array();
		}
		foreach($this->sessionStats as $key => $value) {
			if(array_key_exists($key, $globalStats)) { 
				$globalStats[$key] += $value;
			} else {
				$globalStats[$key] = 0;
			}
		}
		if($save) {
			$this->set('megacache-globalstats', $globalStats);
		}
		return $globalStats;
	}
	
	/**
	 * saveStore function.
	 * 
	 * @access public
	 * @return void
	 */
	public function saveStore() {
		$this->set('megacache-store', $this->store);
	}
	
	/* Array Access implementation */
	/**
	 * offsetExists function.
	 * 
	 * @access public
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetExists ($offset) {
		return isset($this->store);
	}
	
	/**
	 * offsetGet function.
	 * 
	 * @access public
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}
	
	/**
	 * offsetSet function.
	 * 
	 * @access public
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		return $this->set($offset, $value);
	}
	
	/**
	 * offsetUnset function.
	 * 
	 * @access public
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		return $this->delete($offset);
	}	
}