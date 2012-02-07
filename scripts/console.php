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
$bootstrap = $application->getBootstrap()->bootstrap();


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
        $message = 'Another installation in progress, aborting';
        echo $message;
        $log->log($message, LOG_INFO);
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
        $message = 'Nothing in pending queue';
        echo $message;
        $log->log($message, LOG_INFO, ' ');
        exit;
    }
    
    $db->update('queue',array('status'=>'installing'),'id='.$queueElement['id']);

    $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');
    $log = new Zend_Log($writer);
 
    $dbhost = $config->resources->db->params->host; //fetch from zend config
    $dbname = $queueElement['login'].'_'.$queueElement['domain'];
    $dbuser = $queueElement['login']; //fetch from zend config
    $dbpass = substr(sha1($config->magento->usersalt.$config->magento->userprefix.$queueElement['login']),0,10); //fetch from zend config
    
    $adminuser = $queueElement['login'];
    $adminpass = $queueElement['domain'];
    $adminfname = $queueElement['firstname'];
    $adminlname = $queueElement['lastname'];

    $magentoVersion = $queueElement['version'];
    $domain = $queueElement['domain'];
    $startCwd =  getcwd();
    
    $message = 'domain: '.$domain;
    $log->log($message, LOG_DEBUG);
    
    $dbprefix = $domain.'_';
    
    $adminemail = $config->magento->adminEmail; //fetch from zend config
    $storeurl = $config->magento->storeUrl.'/instance/'.$domain; //fetch from zend config
    $message = 'store url: '.$storeurl;
    $log->log($message, LOG_DEBUG);
    
    chdir(INSTANCE_PATH);
    
    echo "Now installing Magento without sample data...\n";
    echo "Preparing directory...\n";
    exec('mkdir '.$domain,$output);
    $message = var_export($output,true);
    $log->log($message, LOG_DEBUG);
    unset($output);
    
    if (!file_exists(INSTANCE_PATH.'/'.$domain) || !is_dir(INSTANCE_PATH.'/'.$domain)){
        $message = 'Directory does not exist, aborting';
        echo $message;
        $log->log($message, LOG_DEBUG);
        exit;
    }
        
    chdir($domain);
    
    echo "Copying package to target directory...\n";
    exec('cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.INSTANCE_PATH.$domain.'/',$output);  
    $message = var_export($output,true);
    $log->log("\ncp ".APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.INSTANCE_PATH.$domain."/\n".$message, LOG_DEBUG);
    unset($output);
    
    echo "Extracting data...\n";
    exec('tar -zxvf magento-' . $magentoVersion . '.tar.gz',$output);  
    $message = var_export($output,true);
    $log->log("\ntar -zxvf magento-" . $magentoVersion . ".tar.gz\n".$message, LOG_DEBUG);
    unset($output);
    
    echo "Moving files...\n";
    exec('mv magento/* .',$output);
    $message = var_export($output,true);
    $log->log("\nmv magento/* .\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('mv magento/.htaccess .',$output);
    $message = var_export($output,true);
    $log->log("\nmv magento/.htaccess .\n".$message, LOG_DEBUG);
    unset($output);
    
    echo "Setting permissions...\n";    
    exec('chmod 777 var/.htaccess app/etc',$output);
    $message = var_export($output,true);
    $log->log("\nchmod 777 var var/.htaccess app/etc\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('chmod 777 var -R',$output);
    $message = var_export($output,true);
    $log->log("\nchmod 777 var var/.htaccess app/etc\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('chmod 777 media -R',$output);
    $message = var_export($output,true);
    $log->log("\nchmod -R 777 media\n".$message, LOG_DEBUG);
    unset($output);
      
    echo "Cleaning up files...\n";
    exec('rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*',$output);
    $message = var_export($output,true);
    $log->log("\nrm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('rm -rf magento/ magento-' . $magentoVersion . '.tar.gz',$output);
    $message = var_export($output,true);
    $log->log("\nrm -rf magento/ magento-" . $magentoVersion . ".tar.gz\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt',$output);
    $message = var_export($output,true);
    $log->log("\nrm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n".$message, LOG_DEBUG);
    unset($output);
    
   
    echo "Installing Magento...\n";
    exec('cd '.INSTANCE_PATH.'/'.$domain.'; /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $config->magento->instanceprefix.$dbname . '"' .
            ' --db_user "' . $config->magento->userprefix.$dbuser . '"' .
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
    $message = var_export($output,true);
    $log->log("\n".'cd '.INSTANCE_PATH.'/'.$domain.'; /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $config->magento->instanceprefix.$dbname . '"' .
            ' --db_user "' . $config->magento->userprefix.$dbuser . '"' .
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
            ' --skip_url_validation "yes"'."\n".$message, LOG_DEBUG);
    unset($output);

    echo "Finished installing Magento\n";


    //TODO: add mail info about ready installation
    
    
    
    $db->update('queue',array('status'=>'ready'),'id='.$queueElement['id']);
    
    chdir($startCwd);
    exit;
}

if (isset($opts->magentoremove)) {
          
    
    $select = new Zend_Db_Select($db);
    $sql = $select
            ->from('queue')
            ->joinLeft('user', 'queue.user_id = user.id',array('email','login'))
            ->where('queue.status =?', 'closed');

    $query = $sql->query();
    $queueElement = $query->fetch();

    
    if (!$queueElement){
        $message = 'Nothing in closed queue';
        echo $message;
        
        
        $log->log($message, LOG_INFO,' ');
        exit;
    }
    
 
    //drop database
    $dbname = $queueElement['login'].'_'.$queueElement['domain'];
    
    $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');
    $log = new Zend_Log($writer);
      
    $DbManager = new Application_Model_DbTable_Privilege($db,$config);
    
    if ($DbManager->checkIfDatabaseExists($dbname)){
        try{
            $DbManager->dropDatabase($dbname);
        } catch(PDOException $e){
            $message = 'Could not remove database for instance';
            echo $message;
            $log->log($message, LOG_ERR);
            exit;
        }
    } else {
        $message = 'database does not exist, ignoring...';
        echo $message;
        $log->log($message, LOG_ERR);
    }
    
    //remove folder recursively
    $startCwd =  getcwd();
    chdir(INSTANCE_PATH);
    rrmdir($queueElement['domain']);
    chdir($startCwd);
    
    $db->getConnection()->exec("use ".$config->resources->db->params->dbname);
    
    $db->delete('queue','id='.$queueElement['id']); 
    unlink(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');
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