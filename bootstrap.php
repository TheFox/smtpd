<?php

declare(ticks = 1);

error_reporting(E_ALL | E_STRICT);

if(@date_default_timezone_get() == 'UTC') date_default_timezone_set('UTC');

ini_set('display_errors', 'On');
ini_set('memory_limit', '128M');

chdir(__DIR__);

#define('DEBUG', 1, true);
define('PHP_EOL_LEN', strlen(PHP_EOL), true);

if(getenv('TEST')){
	define('TEST', true, true);
}
else{
	define('TEST', false, true);
}


if(PHP_SAPI != 'cli'){
	print 'FATAL ERROR: you need to run this in your shell'."\n";
	exit(1);
}
if(version_compare(PHP_VERSION, '5.3.0', '<')){
	print 'FATAL ERROR: you need at least PHP 5.3. Your version: '.PHP_VERSION."\n";
	exit(1);
}

// Check modules installed.
if(!extension_loaded('sockets')){
	print 'FATAL ERROR: you must first install sockets.'."\n";
	exit(1);
}

# TODO: use DIRECTORY_SEPARATOR
if(!file_exists(__DIR__.'/vendor')){
	print "FATAL ERROR: you must first run 'composer install'.\nVisit https://getcomposer.org\n";
	exit(1);
}

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Filesystem\Filesystem;

$filesystem = new Filesystem();
$filesystem->mkdir('log', 0700);
$filesystem->mkdir('pid', 0700);
