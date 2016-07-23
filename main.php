<?php
function __autoload($class) {
	require_once(preg_replace('/_/', '/', $class).'.php');
}

Reader_StdIn::open();
if (file_exists('config.data')) {
    $raw = file_get_contents('config.data');
    $dbConfig = unserialize($raw);
    $dbConfig->readDbStructure();
} else {
    $dbConfig = new Config_Database();
}

$dbConfig->process();

$dbConfig->clear();

$raw = serialize($dbConfig);
file_put_contents('config.data', $raw);

Reader_StdIn::close();