<?php
error_reporting(E_ALL);
require_once('../lib/CacheFactory.php');
$cache = CacheFactory::factory('sqlite', array('cachePath' => dirname(__FILE__) . '/cache', 'cacheName' => 'test'));
require_once('scenario.php');
