<?php
error_reporting(E_ALL);
require_once('../lib/CacheFactory.php');
$cache = CacheFactory::factory('memcache', array('servers' => array(array('host' => 'localhost')), 'cacheName' => 'mycache'));
include('scenario.php');
