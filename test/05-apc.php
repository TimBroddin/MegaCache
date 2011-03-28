<?php
error_reporting(E_ALL);
require_once('../lib/CacheFactory.php');
$cache = CacheFactory::factory('apc', array('cacheName' => 'mycache'));
require_once('scenario.php');