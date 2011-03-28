<?php
/**
 * CacheInterface file
 * Contains the cacheInterface interface
 *
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */

/**
 * cacheInterface interface.
 * @author Tim Broddin <tim@brodd.in>
 * @package MegaCache
 */
interface cacheInterface {
	public function get($varName);
	public function set($varName, $value, $timeout=0);
	public function delete($varName);
	public function increment($varName, $with=1);
	public function decrement($varName, $with=1);
}