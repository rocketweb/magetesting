 <?php
/**
 * Have a look at the comment inside 
 * if ($queueElement['has_system_account'] == 0){
 * or be prepared for this script to not work! 
 */
 
 
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
    $output='';
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
            ->joinLeft('version', 'queue.version_id = version.id',array('version','sample_data_version'))
            ->joinLeft('user', 'queue.user_id = user.id',array('email','login','firstname','lastname','has_system_account'))
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
    
    $instanceFolder = $config->magento->systemHomeFolder.'/'.$config->magento->userprefix.$dbuser.'/public_html';
    if ($queueElement['has_system_account'] == 0){
        $db->update('user',array('system_account_name'=>$config->magento->userprefix.$dbuser),'id='.$queueElement['user_id']);
        
        /** WARNING! 
         * in order for this to work, when you run this (console.php) file, 
         * you need to cd to this (scripts) folder first, like this:
         // * * * * * cd /var/www/magentointegration/scripts/; php console.php --magentoinstall
         *
         */
        exec('sudo ./create_user.sh '.$config->magento->userprefix.$dbuser.' '.$dbpass.' '.$config->magento->usersalt.' '.$config->magento->systemHomeFolder,$output);
        $message = var_export($output,true);
        $log->log($message, LOG_DEBUG);
        unset($output);
        
        /*send email with account details start*/
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
        // assign valeues
        $html->assign('ftphost', $config->magento->ftphost);
        $html->assign('ftpuser', $config->magento->userprefix.$dbuser);
        $html->assign('ftppass', $dbpass);
        
        $html->assign('dbhost', $config->magento->dbhost);
        $html->assign('dbuser', $config->magento->userprefix.$dbuser);
        $html->assign('dbpass', $dbpass);
        
        $html->assign('storeUrl', $config->magento->storeUrl);
        
        // render view
        $bodyText = $html->render('system-account-created.phtml');

        // create mail object
        $mail = new Zend_Mail('utf-8');
        // configure base stuff
        $mail->addTo($queueElement['email']);
        $mail->setSubject($config->cron->systemAccountCreated->subject);
        $mail->setFrom($config->cron->systemAccountCreated->from->email,$config->cron->systemAccountCreated->from->desc);
        $mail->setBodyHtml($bodyText);
        $mail->send();
        /*send email with account details stop*/
        
    }
    $adminuser = $queueElement['login'];   
    $adminpass = substr(
            substr(
                    str_shuffle(
                            str_repeat('0123456789', 5)
                    )
                    , 0, 4) .
            str_shuffle(
                    str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 5)
            )
            , 0, 5);
    
    $adminfname = $queueElement['firstname'];
    $adminlname = $queueElement['lastname'];

    $magentoVersion = $queueElement['version'];
    $sampleDataVersion = $queueElement['sample_data_version'];
    $installSampleData = $queueElement['sample_data'];
    
    $domain = $queueElement['domain'];
    $startCwd =  getcwd();
   
    $message = 'domain: '.$domain;
    $log->log($message, LOG_DEBUG);
    
    $dbprefix = $domain.'_';
    
    $adminemail = $config->magento->adminEmail; //fetch from zend config
    $storeurl = $config->magento->storeUrl.'/instance/'.$domain; //fetch from zend config
    $message = 'store url: '.$storeurl;
    $log->log($message, LOG_DEBUG);
    
    chdir($instanceFolder);  
    
    if ($installSampleData){
        echo "Now installing Magento with sample data...\n";    
    } else {
        echo "Now installing Magento without sample data...\n";    
    }
    
    echo "Preparing directory...\n";
    exec('sudo mkdir '.$instanceFolder.'/'.$domain,$output);
    $message = var_export($output,true);
    $log->log($message, LOG_DEBUG);
    unset($output);
       
    if (!file_exists($instanceFolder.'/'.$domain) || !is_dir($instanceFolder.'/'.$domain)){
        $message = 'Directory does not exist, aborting';
        echo $message;
        $log->log($message, LOG_DEBUG);
        
    }
    
    exec('sudo chmod +x '.$instanceFolder.'/'.$domain,$output);
    $message = var_export($output,true);
    $log->log('chmodding domain: '.$message, LOG_DEBUG);
    unset($output);
        
    chdir($domain);
    
    echo "Copying package to target directory...\n";
    exec('sudo cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.$instanceFolder.'/'.$domain.'/',$output);  
    $message = var_export($output,true);
    $log->log("\nsudo cp ".APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.$instanceFolder.'/'.$domain."/\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('sudo cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/keyset0.sql '.$instanceFolder.'/'.$domain.'/');  
    exec('sudo cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/keyset1.sql '.$instanceFolder.'/'.$domain.'/');  
    
    if ($installSampleData){
        echo "Copying sample data package to target directory...\n";
        exec('sudo cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-sample-data-'. $sampleDataVersion .'.tar.gz '.$instanceFolder.'/'.$domain.'/',$output);  
        $message = var_export($output,true);
        $log->log("\nsudo cp ".APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-sample-data-'. $sampleDataVersion .'.tar.gz '.$instanceFolder.'/'.$domain."/\n".$message, LOG_DEBUG);
        unset($output);
    }
    
    echo "Extracting data...\n";
    exec('sudo tar -zxvf magento-' . $magentoVersion . '.tar.gz',$output);  
    $message = var_export($output,true);
    $log->log("\nsudo tar -zxvf magento-" . $magentoVersion . ".tar.gz\n".$message, LOG_DEBUG);
    unset($output);
    
    if ($installSampleData){
        echo "Extracting sample data...\n";
        exec('sudo tar -zxvf magento-sample-data-' . $sampleDataVersion . '.tar.gz',$output);  
        $message = var_export($output,true);
        $log->log("\nsudo tar -zxvf magento-sample-data-" . $sampleDataVersion . ".tar.gz\n".$message, LOG_DEBUG);
        unset($output);
        
        echo "Moving sample data files...\n";
        exec('sudo mv magento-sample-data-'.$sampleDataVersion.'/* .',$output);
        $message = var_export($output,true);
        $log->log("\nsudo mv mv magento-sample-data-".$sampleDataVersion."/* .\n".$message, LOG_DEBUG);
        unset($output);
    }
    
    echo "Moving files...\n";
    exec('sudo cp -R magento/* .',$output);
    $message = var_export($output,true);
    $log->log("\nsudo cp -R magento/* .\n".$message, LOG_DEBUG);
    unset($output);
       
    exec('sudo cp magento/.htaccess .',$output);
    $message = var_export($output,true);
    $log->log("\nsudo cp magento/.htaccess .\n".$message, LOG_DEBUG);
    unset($output);
    
    rrmdir('magento');
    
    echo "Setting permissions...\n";    
    exec('sudo chmod 777 var/.htaccess app/etc',$output);
    $message = var_export($output,true);
    $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('sudo chmod 777 var -R',$output);
    $message = var_export($output,true);
    $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('sudo chmod 777 media -R',$output);
    $message = var_export($output,true);
    $log->log("\nsudo chmod -R 777 media\n".$message, LOG_DEBUG);
    unset($output);
      
    if ($installSampleData){
        echo "Inserting sample data\n";
        exec('sudo mysql -u'.$config->magento->userprefix.$dbuser.' -p'.$dbpass.' '.$config->magento->instanceprefix.$dbname.' < magento_sample_data_for_'.$sampleDataVersion.'.sql');
    }
    
    echo "Cleaning up files...\n";
    exec('sudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*',$output);
    $message = var_export($output,true);
    $log->log("\nsudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('sudo rm -rf magento/ magento-' . $magentoVersion . '.tar.gz',$output);
    $message = var_export($output,true);
    $log->log("\nsudo rm -rf magento/ magento-" . $magentoVersion . ".tar.gz\n".$message, LOG_DEBUG);
    unset($output);
    
    exec('sudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt',$output);
    $message = var_export($output,true);
    $log->log("\nsudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n".$message, LOG_DEBUG);
    unset($output);
    
    if ($installSampleData){
         exec('sudo rm -rf magento-sample-data-'.$sampleDataVersion.'/ magento-sample-data-' . $sampleDataVersion . '.tar.gz magento_sample_data_for_'.$sampleDataVersion.'.sql',$output);
        $message = var_export($output,true);
        $log->log("\nsudo rm -rf magento-sample-data-" . $sampleDataVersion . "/ magento-sample-data-".$sampleDataVersion.".tar.gz magento_sample_data_for_".$sampleDataVersion.".sql\n".$message, LOG_DEBUG);
        unset($output);      
    }
       
    echo "Installing Magento...\n";
    exec('sudo mysql -u'.$config->magento->userprefix.$dbuser.' -p'.$dbpass.' '.$config->magento->instanceprefix.$dbname.' < keyset0.sql');
    exec('cd '.$instanceFolder.'/'.$domain.';sudo  /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $config->magento->instanceprefix.$dbname . '"' .
            ' --db_user "' . $config->magento->userprefix.$dbuser . '"' .
            ' --db_pass "' . $dbpass . '"' .
            //' --db_prefix "' . $dbprefix . '"' .
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
    exec('sudo mysql -u'.$config->magento->userprefix.$dbuser.' -p'.$dbpass.' '.$config->magento->instanceprefix.$dbname.' < keyset1.sql');
    
    $log->log("\n".'cd '.$instanceFolder.'/'.$domain.';sudo /usr/bin/php -f install.php --' .
            ' --license_agreement_accepted "yes"' .
            ' --locale "en_US"' .
            ' --timezone "America/Los_Angeles"' .
            ' --default_currency "USD"' .
            ' --db_host "' . $dbhost . '"' .
            ' --db_name "' . $config->magento->instanceprefix.$dbname . '"' .
            ' --db_user "' . $config->magento->userprefix.$dbuser . '"' .
            ' --db_pass "' . $dbpass . '"' .
            //' --db_prefix "' . $dbprefix . '"' .
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
    
    exec('ln -s '.$instanceFolder.'/'.$domain.' '.INSTANCE_PATH.$domain);
    
    $db->update('queue',array('status'=>'ready'),'id='.$queueElement['id']);
          
    chdir($startCwd);
    
    /*send email to instance owner start*/
    $html = new Zend_View();
    $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
    // assign valeues
    $html->assign('domain', $domain);
    $html->assign('storeUrl', $config->magento->storeUrl);
    $html->assign('admin_login', $adminuser);
    $html->assign('admin_password', $adminpass);
    // render view
    $bodyText = $html->render('queue-item-ready.phtml');

    // create mail object
    $mail = new Zend_Mail('utf-8');
    // configure base stuff
    $mail->addTo($queueElement['email']);
    $mail->setSubject($config->cron->queueItemReady->subject);
    $mail->setFrom($config->cron->queueItemReady->from->email,$config->cron->queueItemReady->from->desc);
    $mail->setBodyHtml($bodyText);
    $mail->send();
    /*send email to instance owner stop*/
    
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
    
    /* todo: replace rrmdir with exec() and system commad  */
    rrmdir($queueElement['domain']);
    chdir($startCwd);
    
    $db->getConnection()->exec("use ".$config->resources->db->params->dbname);
    
    $db->delete('queue','id='.$queueElement['id']); 
    unlink(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');
}

function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
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
