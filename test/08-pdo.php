<?php
error_reporting(E_ALL);
require_once('../lib/CacheFactory.php');
$cache = CacheFactory::factory('pdo', array('dsn' => 'mysql:host=localhost;dbname=', 'username' => '', 'password' => ''));
include('scenario.php');
