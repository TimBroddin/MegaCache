<?php
/**
 * CacheFactory file
 * Contains the CacheFactory class
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */
require('CacheInterface.php');
require('BaseCache.php');
/**
 * CacheFactory class.
 * returns right cache object 
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */
class CacheFactory {
	/**
	 * factory function.
	 * 
	 * @access public
	 * @param array $configuration
	 *		an arry containing all configuration options
	 *      consult the documentation or the individual adapters code
	 *		to view what options to pass
	 * @return cache object
	 */
	static function factory($type, $configuration=array()) {
		if(!$type) {
			throw new Exception('You have to provide at least a type to the cache factory.');
		}
		$name = 'Cache' . ucfirst($type);
		require('adapters/' . $name . '.php');
		return new $name($configuration);
	}
}

