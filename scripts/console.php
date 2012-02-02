<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/'));
define('APPLICATION_ENV', 'development');
define('INSTANCE_PATH',APPLICATION_PATH . '/../instance/');
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
// Create application, bootstrap, and run
$application = new Zend_Application(
                APPLICATION_ENV,
                APPLICATION_PATH . '/configs/application.ini'
);

try {
    $opts = new Zend_Console_Getopt(
                    array(
                        'help' => 'Displays help.',
                        'hello' => 'try it !',
                        'magentoinstall' => 'handles magento from install queue',
                        'magentoremove' => 'handles magento from remove queue',
                    )
    );

    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    exit($e->getMessage() . "\n\n" . $e->getUsageMessage());
}

if (isset($opts->help)) {
    echo $opts->getUsageMessage();
    exit;
}

/**
 * Action : hello
 */
if (isset($opts->hello)) {
    echo "Hello World!\n";
}

/**
 * Action : magentoinstall
 */
if (isset($opts->magentoinstall)) {


    $bootstrap = $application->getBootstrap()->bootstrap();
    $db = $bootstrap->getResource('db');

    $select = new Zend_Db_Select($db);
    
    //check if any script is currently being installed
    $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id',array('version'))
            ->joinLeft('user', 'queue.user_id = user.id',array('email'))
            ->where('queue.status =?', 'installing');

    $query = $sql->query();
    $queueElement = $query->fetch();
    
    if ($queueElement){
        //something is currently installed, abort
        echo 'Another installation in progress, aborting';
        exit;
    }
    
    $select = new Zend_Db_Select($db);
    $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id',array('version'))
            ->joinLeft('user', 'queue.user_id = user.id',array('email','login','firstname','lastname'))
            ->where('queue.status =?', 'pending')
            ->where('user.status =?', 'active')
    ;

    $query = $sql->query();
    $queueElement = $query->fetch();

    
    if (!$queueElement){
        echo 'Nothing in queue';
        exit;
    }
    
    $db->update('queue',array('status'=>'installing'),'id='.$queueElement['id']);

    $options['nestSeparator'] = ':';
    $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',
                    'production',
                    $options);

    $configLocal = new Zend_Config_Ini(APPLICATION_PATH . '/configs/local.ini',
                    'production',
                    $options);

    $configLocalArr = $configLocal->toArray();
    $configArr = $config->toArray();

    
    $dbhost = $configArr['resources.db.params.host']; //fetch from zend config
    $dbname = $queueElement['login'].'__'.$queueElement['domain'];
    
    //
    try{
        $db->getConnection()->exec("CREATE DATABASE ".$dbname);   
    } catch(PDOException $e){
        echo 'Could not create database for instance, aborting';
        $db->update('queue',array('status'=>'pending'),'id='.$queueElement['id']);
        exit;
    }
    
    $dbuser = $configArr['resources.db.params.username']; //fetch from zend config
    $dbpass = $configArr['resources.db.params.password']; //fetch from zend config
    
    $adminuser = $queueElement['login'];
    $adminpass = $queueElement['domain'];
    $adminfname = $queueElement['firstname'];
    $adminlname = $queueElement['lastname'];

    $magentoVersion = $queueElement['version'];
    $domain = $queueElement['domain'];
    $startCwd =  getcwd();
    $log_file_path = $startCwd.'/'.$domain.'_install_log.txt';
    
    
    file_put_contents($log_file_path, "\ndomain: ".$domain , FILE_APPEND);
    unset($output);
    $dbprefix = $domain.'__';
    
    $adminemail = $configLocalArr['magento.adminEmail']; //fetch from zend config
    $storeurl = $configLocalArr['magento.storeUrl'].'/instance/'.$domain; //fetch from zend config
    file_put_contents($log_file_path, "\nstore url: ".$storeurl , FILE_APPEND);
    
    chdir(INSTANCE_PATH);
    unset($output);
    
    echo "Now installing Magento without sample data...\n";
    echo "Preparing directory...\n";
    exec('mkdir '.$domain,$output);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    if (!file_exists(INSTANCE_PATH.'/'.$domain) || !is_dir(INSTANCE_PATH.'/'.$domain)){
        echo 'Directory does not exist, aborting';
        file_put_contents($log_file_path, "\nDirectory does not exist, aborting\n" , FILE_APPEND);
        exit;
    }
        
    chdir($domain);
    echo "Copying package to target directory...\n";
    exec('cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.INSTANCE_PATH.$domain.'/',$output);  
    file_put_contents($log_file_path,"\ncp ".APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.INSTANCE_PATH.$domain."/\n", FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    echo "Extracting data...\n";
    exec('tar -zxvf magento-' . $magentoVersion . '.tar.gz',$output);
    file_put_contents($log_file_path, "\ntar -zxvf magento-" . $magentoVersion . ".tar.gz\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    echo "Moving files...\n";
    exec('mv magento/* .',$output);
    file_put_contents($log_file_path, "\nmv magento/* .\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    exec('mv magento/.htaccess .',$output);
    file_put_contents($log_file_path, "\nmv magento/.htaccess .\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    echo "Setting permissions...\n";    
    exec('chmod 777 var/.htaccess app/etc',$output);
    file_put_contents($log_file_path, "\nchmod 777 var var/.htaccess app/etc\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    exec('chmod 777 var -R',$output);
    file_put_contents($log_file_path, "\nchmod 777 var var/.htaccess app/etc\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    exec('chmod 777 media -R',$output);
    file_put_contents($log_file_path, "\nchmod -R 777 media\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    exec('chmod 777 mage',$output);
    file_put_contents($log_file_path, "\nchmod 777 mage\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    echo "Cleaning up files...\n";
    exec('rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*',$output);
    file_put_contents($log_file_path, "\nrm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    exec('rm -rf magento/ magento-' . $magentoVersion . '.tar.gz',$output);
    file_put_contents($log_file_path, "\nrm -rf magento/ magento-" . $magentoVersion . ".tar.gz\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
    exec('rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt',$output);
    file_put_contents($log_file_path, "\nrm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);
    
   
    echo "Installing Magento...\n";
    exec('cd '.INSTANCE_PATH.'/'.$domain.'; /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $dbname . '"' .
            ' --db_user "' . $dbuser . '"' .
            ' --db_pass "' . $dbpass . '"' .
            ' --db_prefix "' . $dbprefix . '"' .
            ' --url "' . $storeurl . '"' .
            ' --use_rewrites "yes"' .
            ' --use_secure "no"' .
            ' --secure_base_url ""' .
            ' --use_secure_admin "no"' .
            ' --admin_firstname "' . $adminfname . '"' .
            ' --admin_lastname "' . $adminlname . '"' .
            ' --admin_email "' . $adminemail . '"' .
            ' --admin_username "' . $adminuser . '"' .
            ' --admin_password "' . $adminpass . '"' .
            ' --skip_url_validation "yes"',$output);
    file_put_contents($log_file_path, "\n".'cd '.INSTANCE_PATH.'/'.$domain.'; /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $dbname . '"' .
            ' --db_user "' . $dbuser . '"' .
            ' --db_pass "' . $dbpass . '"' .
            ' --db_prefix "' . $dbprefix . '"' .
            ' --url "' . $storeurl . '"' .
            ' --use_rewrites "yes"' .
            ' --use_secure "no"' .
            ' --secure_base_url ""' .
            ' --use_secure_admin "no"' .
            ' --admin_firstname "' . $adminfname . '"' .
            ' --admin_lastname "' . $adminlname . '"' .
            ' --admin_email "' . $adminemail . '"' .
            ' --admin_username "' . $adminuser . '"' .
            ' --admin_password "' . $adminpass . '"' .
            ' --skip_url_validation "yes"'."\n" , FILE_APPEND);
    file_put_contents($log_file_path, var_export($output,true) , FILE_APPEND);
    unset($output);

    echo "Finished installing Magento\n";
    file_put_contents($log_file_path, "\nfinished installation " , FILE_APPEND);
    unset($output);
    //TODO: add mail info about ready installation
    
    
    //$update = new Zend_Db_Update($db);
    $db->update('queue',array('status'=>'ready'),'id='.$queueElement['id']);
    
    chdir($startCwd);
    exit;
}

if (isset($opts->magentoremove)) {
    
    $bootstrap = $application->getBootstrap()->bootstrap();
    $db = $bootstrap->getResource('db');
      
    $select = new Zend_Db_Select($db);
    $sql = $select
            ->from('queue')
            ->joinLeft('user', 'queue.user_id = user.id',array('email','login'))
            ->where('queue.status =?', 'closed');

    $query = $sql->query();
    $queueElement = $query->fetch();

    
    if (!$queueElement){
        echo 'Nothing in queue';
        exit;
    }
    
 
    //drop database
    $dbname = $queueElement['login'].'__'.$queueElement['domain'];
  
    try{
        $db->getConnection()->exec("DROP DATABASE ".$dbname);   
    } catch(PDOException $e){
        echo 'Could not remove database for instance';
        exit;
    }
    
    //remove install log if exist
    if (file_exists($queueElement['domain'].'_install_log.txt')){
    unlink($queueElement['domain'].'_install_log.txt');
    }
         
    //remove folder recursively
    $startCwd =  getcwd();
    chdir(INSTANCE_PATH);
    rrmdir($queueElement['domain']);
    chdir($startCwd);
    
    $db->delete('queue','id='.$queueElement['id']); 
    
}

function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     //var_dump($objects);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
            if (filetype($dir."/".$object) == "dir") {
                rrmdir($dir."/".$object);
            } else {
                unlink($dir."/".$object);
            }
       }
     }
     reset($objects);
     rmdir($dir);
   }
}