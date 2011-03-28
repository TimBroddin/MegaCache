<?php
error_reporting(E_ALL);
require_once('../lib/CacheFactory.php');
$cache = CacheFactory::factory('null');
require_once('scenario.php');
include('scenario.php');

