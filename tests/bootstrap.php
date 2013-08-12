<?php

// start output buffering
ob_start();

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

// Set the default timezone !!!
date_default_timezone_set('Europe/Warsaw');

// We wanna catch all errors en strict warnings
error_reporting(E_ALL|E_STRICT);
require_once 'Zend/Application.php';
require_once 'ControllerTestCase.php';