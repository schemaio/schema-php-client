<?php

if (!function_exists('json_decode')) {
    throw new Exception('Schema API Client requires the JSON PHP extension');
}

$basedir = dirname(__FILE__);
require_once($basedir.'/Schema/Cache.php');
require_once($basedir.'/Schema/Client.php');
require_once($basedir.'/Schema/Connection.php');
require_once($basedir.'/Schema/Resource.php');
require_once($basedir.'/Schema/Collection.php');
require_once($basedir.'/Schema/Record.php');
