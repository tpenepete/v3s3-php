<?php
define('V3S3', 1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__.DIRECTORY_SEPARATOR.'defines.php');

spl_autoload_register(function ($class) {
	if (file_exists($file = __DIR__ .DS.'Module'.DS.str_replace('\\', '/', $class).'.php')) {
		require $file;
	} else if(file_exists($file = DIR_INCLUDES.DS.$class.'.php')) {
		require $file;
	}
});

$config = [];

require_once(DIR_GLOBAL_CONFIG.DS.'config.php');
require_once(DIR_LOCAL_CONFIG.DS.'config.php');

$db = new db_PDO($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database'], $config['db']['buffer_size']);
if (empty($db->status)) {
	die('Error connecting to DB.');
}

$translator = new translator();
router::route();