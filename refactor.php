<?php

error_reporting( -1 );
ini_set('display_errors', 1);
// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
realpath(APPLICATION_PATH . '/../library'),

// turning this off to not look within folders outside open_basedir
//    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
Zend_Session::start();

$queries = array();

$cli = new RocketWeb_Cli();
/* @var $file RocketWeb_Cli_Kit_File */
$file = $cli->kit('file');
/* @var $sshConnection RocketWeb_Cli_Kit_Ssh */
$sshConnection = $cli->kit('ssh');


$login = 'login';
$password = 'pass';
$host = 'host';
$port = 'port';

$sshConnection->connect(
    $login,
    $password,
    $host,
    $port
);
$queries[] = $sshConnection->toString();
# -------- application/controllers/QueueController.php
# 456
$appPaths = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->find('app', $file::TYPE_DIR, '.')->printPaths()->sortNatural()
);

$queries[] = $appPaths->toString();
# 466
$skinPaths = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->find('skin', $file::TYPE_DIR, '.')->printPaths()->sortNatural()
);

$queries[] = $skinPaths->toString();
# 475
$libPaths = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->find('lib', $file::TYPE_DIR, '.')->printPaths()->sortNatural()
);

$queries[] = $libPaths->toString();
# 484
$jsPaths = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->find('js', $file::TYPE_DIR, '.')->printPaths()->sortNatural()
);

$queries[] = $jsPaths->toString();
# 502
$magePaths = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->find('Mage.php', $file::TYPE_FILE, '.')->printPaths(true)->sortNatural()
);

$queries[] = $magePaths->toString();
# 645
$findBackups = $sshConnection->cloneObject()->remoteCall(
    $file->clear()->listAll('.')
);

$queries[] = $findBackups->toString();

# -------- application/models/Task/Extension/Install.php
/* @var $tar RocketWeb_Cli_Kit_Compression_Tar */
$tar = $cli->kit('tar');
# 106
$unpack = $tar->cloneObject()->isCompressed(true)->unpack('some.tar.gz', 'temp_dir');
$queries[]= $unpack->toString();
# 121
$chown = $file->clear()->fileOwner('path_to_files', 'user:user', true);
$queries[] = $chown->toString();
# 128
$find =
    $file
        ->clear()
        ->find('tmpExtensionDir', $file::TYPE_DIR)
        ->printFiles()
        ->pipe(
            $file->newQuery('xargs')->fileMode('', 0755, false)
        );
$queries[] = $find->toString();
# 135
$find =
    $file
        ->clear()
        ->find('tmpExtensionDir', $file::TYPE_FILE)
        ->printFiles()
        ->pipe(
            $file->newQuery('xargs')->fileMode('', 0644, false)
        );
$queries[] = $find->toString();
# 142
$copy = $file->clear()->copy('a', 'b', true);
$queries[] = $copy->toString();
# 148
$rm = $file->clear()->delete('path to remove')->asSuperUser(true);
$queries[] = $rm->toString();

# -------- application/models/Task/Extension/Opensource.php
# 50
$unpack = $tar->clear()->isCompressed(true)->unpack('some_file_to_extract', 'path_to_move_files');
$queries[] = $unpack->toString();

# -------- application/models/Task/Magento/Download.php
# 103
$query = $file->clear()->copy('.htaccess', '.htaccess')->asSuperUser(true);
$queries[] = $query->toString();
# 111
$query = $file->clear()->fileOwner('path', 'user:user', true)->asSuperUser(true);
$queries[] = $query->toString();
# 141
$query = $file->clear()->create('store/dir', $file::TYPE_DIR);
$queries[] = $query->toString();
# 156
$query = $file->clear()->fileMode('store/dir', '+x', false);
$queries[] = $query->toString();
# 167
/* @var $mysql RocketWeb_Cli_Kit_Mysql */
$mysql = $cli->kit('mysql');
$mysql->connect('user', 'password', 'database');
$query = $mysql->cloneObject()->import('file_to_import');
$queries[] = $query->toString();

echo '<pre>';
var_dump($queries);