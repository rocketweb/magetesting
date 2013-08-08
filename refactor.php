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
# 248
$query = $file->clear()->create('var', $file::TYPE_DIR);
$queries[] = $query->toString();
# 249
$query = $file->clear()->create('.htaccecss', $file::TYPE_FILE);
$queries[] = $query->toString();
# 254
# consider using only echo content > file instead of touch before
$query = $cli->createQuery('echo ? >> ?', array('file content', '.htaccess'));
$queries[] = $query->toString();
# 259
$query = $file->clear()->create('downloader', $file::TYPE_DIR);
$queries[] = $query->toString();
# 263
# consider  creating dirs all at once by appending to mkdir
$query = $file->clear()->create('media', $file::TYPE_DIR);
$queries[] = $query->toString();
# 264
$query = $file->clear()->create('.htaccecss', $file::TYPE_FILE);
$queries[] = $query->toString();
# 291
# consider using only echo content > file instead of touch before
$query = $cli->createQuery('echo ? >> ?', array('file content', '.htaccess'));
$queries[] = $query->toString();
# 299
$query = $file->clear()->fileMode('storeFolder', 'a-w');
$queries[] = $query->toString();
# 306, 312, 319, 326
$query = $file->clear()->fileMode('media', '0777', true)->append('? ? ?', array('downloader', 'var', 'app/etc'));
$queries[] = $query->toString();
# 333
$query = $file->clear()->delete('.git/');
$queries[] = $query->toString();
# 337
$query = $file->clear()->delete('.gitignore');
$queries[] = $query->toString();
# 341
$query = $cli->createQuery('rm -rf `:subquery`')->asSuperUser(true)->bindAssoc(
    ':subquery',
    $file->clear()->find('.svn', $file::TYPE_FILE, '.')->asSuperUser(false),
    false
);
$queries[] = $query->toString();
# 568
/* @var $gzip RocketWeb_Cli_Kit_Compression_Gzip */
$gzip = $cli->kit('gzip');
$query = $gzip->test('sqlfile');
$queries[] = $query->toString();
# 568
$query = $tar->clear()->test('sqlfile');
$queries[] = $query->toString(); # and test status
# 620
$query = $gzip->clear()->getPackedFilename('filename');
$queries[] = $query->toString();
# 647
$query = $cli->createQuery('sudo grep -lir ? ?', array('CREATE TABLE', 'sqlfile'));
$queries[] = $query->toString();
# 680
$query = $cli->createQuery('grep core_config_data ?', 'filename');
$queries[] = $query->toString();
# 681
$query = $cli->createQuery('grep -i -e \'[a-z0-9$_]*core_config_data\' ? -o', 'file')
             ->pipe('head -n 1')
             ->pipe('sed s/core_config_data//');
$queries[] = $query->toString();
# 692
$query = $file->clear()->fileOwner('domain', 'user:user');
$queries[] = $query->toString();

# -------- application/models/Task/Magento/Hourlyrevert.php
# 18
$query = $file->clear()->create('lockfile', $file::TYPE_FILE);
$queries[] = $query->toString();
# 35
$query = $tar->clear()->redirectToOutput()->unpack('asd')->pipe(
    $mysql->clear()->connect('user', 'pass', 'database')->import()
);
$queries[] = $query->toString();
# 39
$query = $file->clear()->delete('somefile');
$queries[] = $query->toString();

# -------- application/models/Task/Magento/Install.php
# 48
$query = $file->clear()->fileMode('', '777', true)->append('?*', 'some/path');
$queries[] = $query->toString();
# 91
$query = $file->clear()->create('domain', $file::TYPE_DIR);
$queries[] = $query->toString();
# 105
$query = $file->clear()->fileMode('domain', '+x');
$queries[] = $query->toString();
# 120, 125, 127, 132
$query = $file->clear()->copy('keyset0.sql', 'domain');
$queries[] = $query->toString();
# 146
$query = $file->clear()->create('domain', $file::TYPE_DIR);
$queries[] = $query->toString();

# -------- application/models/Task/Papertrail/Create.php
/* @var $service RocketWeb_Cli_Kit_Service */
$service = $cli->kit('service');
# 58
$query = $file->clear()->create('filename', $file::TYPE_FILE);
# 101
$query = $service->restart('rsyslog')->asSuperUser(true);
$queries[] = $query->toString();

# -------- application/models/Task/Revision/Commit.php
/* @var $git RocketWeb_Cli_Kit_Git */
$git = $cli->kit('git');
# 59
$query = $git->addAll();
$queries[] = $query->toString();
# 68
$query = $git->commit('commit message');
$queries[] = $query->toString();
# 211
$query = $mysql->clear()->connect('user', 'login', 'database')
               ->export($mysql::EXPORT_DATA_AND_SCHEMA, array('a','b','c'));
$queries[] = $query->toString();

# -------- application/models/Task/Revision/Deploy.php
# 48
$query = $file->clear()->fileMode('var/deployment');
$queries[] = $query->toString();
# 61
$query = $git->clear()->deploy('revision_hash', 'var/deployement/revision_hash.zip');
$queries[] = $query->toString();

# -------- application/models/Task/Revision/Rollback.php
# 34
$query = $git->clear()->rollback('revision_hash');
$queries[] = $query->toString();

# -------- application/models/Task/Magento.php
/* @var $user RocketWeb_Cli_Kit_User */
$user = $cli->kit('user');
# 119
$query = $user->create('user', 'password', 'salt', 'mi_user');
$queries[] = $query->toString();
# 357
$query = $cli->createQuery('-u ? -s ', 'user')->asSuperUser(true);
$query->append('php ? --reindex all', '/shell/indexer.php');
$queries[] = $query->toString();
# 363
$query = $cli->createQuery('quotatool -u :user -b -q :softLimit -l :hardLimit /');
$query->asSuperUser(true);
$query->bindAssoc(array(
    ':user' => 'user',
    ':softLimit' => '4000M',
    ':hardLimit' => '5000M'
));
$queries[] = $query->toString();
# 370
$query = $cli->createQuery('quotatool -u :user -b -t ?', '0 seconds');
$query->bindAssoc(':user', 'user')->asSuperUser(true);
$queries[] = $query->toString();

# -------- application/models/Transport/Ftp.php
/* @var $wget RocketWeb_Cli_Kit_Wget */
$wget = $cli->kit('wget');
$wget->ftpConnect('user', 'password', 'http://somewhere.com', 22);
$wget->addLimits(30, 2);
# 26
$query = $wget->cloneObject()->checkOnly(true);
$queries[] = $query->toString();
# 108
$query = $wget->cloneObject()->downloadFile('public_html/index.php');
$query->getFileSize();
$queries[] = $query->toString();
# 143
$query = $wget->cloneObject()->downloadRecursive('a,b,c,dsa');
$queries[] = $query->toString();

# -------- application/models/Transport/Ssh.php
# 117
$query = $sshConnection->cloneObject()->remoteCall(
    $cli->createQuery('cd /;')->append($tar->clear()->pack('-', 'customPath')->exclude(array('media', 'var'))->toString())
)->pipe(
    $tar->clear()->unpack('-', '.')->strip(5)->isCompressed(true)
);
$queries[] = $query->toString();
# 247
$query = $file->clear()->getSize('custom_file');
$queries[] = $query->toString();

# -------- application/models/User.php
# 597
$query = $user->clear()->addFtp('userlogin');
$queries[] = $query->toString();
# 619
$query = $user->clear()->removeFtp('userlogin');
$queries[] = $query->toString();
# 686
$query = $user->clear()->rebuildPhpMyAdmin('list');
$queries[] = $query->toString();

echo '<pre>';
var_dump($queries);