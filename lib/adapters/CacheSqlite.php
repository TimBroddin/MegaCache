<?php
/**
 * CacheSqlite file
 * Contains the CacheSqlite class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */
 
/**
 * CacheSqlite class.
 *
 * Uses an Sqlite database as storage engine
 * Tends to be very quick for gets and slower for sets and deletes
 *
 * Requires two configuration options: cachePath and cacheName - see below
 * 
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class CacheSqlite extends BaseCache {
	private $db;
	
	/**
	 * __construct function.
	 * 
	 * the configuration array passed must contain these properties:
	 *	- cachePath: the path where to store the database (path to the directory without trailing slash)
	 *	- cacheName: the name of the file where to save to
	 *
	 * The database will be created if it doesn't exist already.
	 *
	 * This adapter requires the sqlite extension
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		if(!extension_loaded('sqlite')) {
			throw new Exception('Please enable the sqlite extension.');
		}
		
		$newInstall = false;
		$path = realpath($config['cachePath']) . "/{$config['cacheName']}.db3";
		
		// if database does not exist touch it
		if(!file_exists($path)) {
			$newInstall = true;
			touch($path);
			chmod($path, 0666);
		}
		
		// connect
		$this->db = sqlite_open($path);
		if(!$this->db) {
			throw new Exception('Can not connect to SQLite database. Please make sure ' . $config['cachePath'] . ' is writable.');
		} elseif($newInstall) {
			$sql = "CREATE TABLE cache (varName VARCHAR(255) NOT NULL, value TEXT NOT NULL, timeout INT NOT NULL, PRIMARY KEY (varName))";
			sqlite_query($this->db, $sql);
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
		$varName = sqlite_escape_string($varName);
		$now = time();
		$result = sqlite_query($this->db, "SELECT value FROM cache WHERE varName='{$varName}' AND timeout > $now");
		if(sqlite_num_rows($result) > 0) {
			$this->sessionStats['hits']++;
			return unserialize(sqlite_fetch_single($result));
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
		if(array_key_exists($varName, $this->store)) {
			$this->delete($varName);
		}
		$varName = sqlite_escape_string($varName);
		$value = sqlite_escape_string(serialize($value));
		$timeout = ($timeout) ? intval(time() + $timeout) : time() + 60*60*24*365*10; // if no timeout is given set 10 years
		sqlite_query($this->db, "INSERT INTO cache(varName, value, timeout) VALUES ('{$varName}', '{$value}', '{$timeout}')");
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
		$varName = sqlite_escape_string($varName);
		sqlite_query($this->db, "DELETE FROM cache WHERE varName='{$varName}'");
		return true;
	}

	/**
	 * clearTimedOut function.
	 * 
	 * delets variables that are timedout
	 *
	 * @access private
	 * @return void
	 */
	private function clearTimedOut() {
		$now = time();
		sqlite_query($this->db, "DELETE FROM cache WHERE timeout <= $now");
	}
	
	public function __destruct() {
		$this->saveStore();
		$this->globalStats();
		sqlite_close($this->db);
	}	
	
}
