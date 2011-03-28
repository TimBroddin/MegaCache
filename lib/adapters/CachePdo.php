<?php
/**
 * CachePDO file
 * Contains the CachePDO class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */
 
/**
 * CachePdo class.
 * 
 * Uses the PDO extension which makes it possible to connect to a number of different database systems.
 * Only tested against MySQL but should work on others
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache 
 * @extends BaseCache
 */
class CachePdo extends BaseCache {
	private $pdo;
	
	/**
	 * __construct function.
	 * 
	 * Configuration array can contain 3 values:
	 *		- dsn: the database source, for example: mysql:host=localhost;dbname=test
	 *		- username
	 *		- password
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config) {
		$this->pdo = new PDO($config['dsn'], $config['username'], $config['password']);
		$this->checkInstall();		
		$this->clearTimedOut();
		parent::__construct($config);
	}
	
	/**
	 * checkInstall function.
	 * 
	 * checks if given table exists - otherwise create table named cache
	 *
	 * @access private
	 * @return void
	 */
	private function checkInstall() {
		$result = $this->pdo->query('SELECT * FROM cache');
		if(!$result) {
			$sql = "CREATE TABLE cache (varName VARCHAR(255) NOT NULL, value TEXT NOT NULL, timeout INT NOT NULL, PRIMARY KEY (varName))";
			$this->pdo->exec($sql);
		}
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
		$varName = $this->pdo->quote($varName);
		$now = time();
		$result = $this->pdo->query("SELECT value FROM cache WHERE varName={$varName} AND timeout > $now");
		if($result && count($result) > 0) {
			$this->sessionStats['hits']++;
			$current = $result->fetch();
			return unserialize($current['value']);
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
		$varName = $this->pdo->quote($varName);
		$value = $this->pdo->quote(serialize($value));
		$timeout = ($timeout) ? intval(time() + $timeout) : time() + 60*60*24*365*10; // if no timeout is given set 10 years
		$this->pdo->query("INSERT INTO cache(varName, value, timeout) VALUES ({$varName}, {$value}, {$timeout})");
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
		$varName = $this->pdo->quote($varName);
		$this->pdo->query("DELETE FROM cache WHERE varName={$varName}");
		return true;
	}
	
	/**
	 * clearTimedOut function.
	 * 
	 * @access public
	 * @return void
	 */
	public function clearTimedOut() {
		$now = time();
		$this->pdo->query("DELETE FROM cache WHERE timeout <= $now");
	}
	
	/**
	 * __destruct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->saveStore();
		$this->globalStats();
	}		
}
