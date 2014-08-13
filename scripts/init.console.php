<?php

/**
* Have a look at the comment inside
* if ($queueElement['has_system_account'] == 0){
* or be prepared for this script to not work!
*/


define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/'));
define('APPLICATION_ENV', 'development');

/**
 * Setup for includes
 */
set_include_path(
        APPLICATION_PATH . '/../library' . PATH_SEPARATOR .
        APPLICATION_PATH . '/../application/models' . PATH_SEPARATOR .
        APPLICATION_PATH . '/../application/extends' . PATH_SEPARATOR .
        get_include_path());

/**
 * Zend Autoloader
 */
require_once 'Zend/Loader/Autoloader.php';

$autoloader = Zend_Loader_Autoloader::getInstance();

/**
 * Register my Namespaces for the Autoloader
 */
//$autoloader->registerNamespace('My_');
$autoloader->registerNamespace('Db_');


/**
 * Include my complete Bootstrap
 * @todo change when time is left
 */


//initialize config
$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV,
        array('allowModifications'=>true));
$localConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/local.ini', APPLICATION_ENV);
$config->merge($localConfig);
$config->setReadOnly();

// Create application, bootstrap, and run
$application = new Zend_Application(
        APPLICATION_ENV,
        $config
);
$bootstrap = $application->getBootstrap()->bootstrap(array('config','db','mailTransport','log','cache'));

//initialize database
$db = $bootstrap->getResource('db');

//initialize logger
if (!$bootstrap->hasResource('Log')) {
    echo 'No logger instance found,aborting';
    exit;
}
$log = $bootstrap->getResource('Log');

//check wheter the log directory is available
if (!file_exists(APPLICATION_PATH . '/../data/logs') || !is_dir(APPLICATION_PATH . '/../data/logs')){
    mkdir(APPLICATION_PATH . '/../data/logs');
}