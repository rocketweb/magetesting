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
                        'magentoinstall' => 'installs magento',
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
            ->joinLeft('user', 'queue.user_id = user.id',array('email'))
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
    $dbname = $configArr['resources.db.params.dbname']; //fetch from zend config
    $dbuser = $configArr['resources.db.params.username']; //fetch from zend config
    $dbpass = $configArr['resources.db.params.password']; //fetch from zend config

    $adminuser = 'admin';
    $adminpass = substr(
            str_shuffle(
                    str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)
            )
            , 0, 8);
    $adminfname = 'Admin';
    $adminlname = 'McAdmin';

    $magentoVersion = $queueElement['version'];
    $domain = $queueElement['domain'];
    $dbprefix = $domain.'_';
    
    $adminemail = $configLocalArr['magento.adminEmail']; //fetch from zend config
    $storeurl = $configLocalArr['magento.storeUrl'].'/instance/'.$domain; //fetch from zend config

    $startCwd =  getcwd();
    chdir(INSTANCE_PATH);
    
    echo "Now installing Magento without sample data...\n";
    echo "Downloading packages...\n";
    exec('mkdir '.$domain);
    
        
    chdir($domain);
    exec('cp '.APPLICATION_PATH.'/../data/pkg/'.$queueElement['edition'].'/magento-'. $magentoVersion .'.tar.gz '.INSTANCE_PATH.$domain.'/');  

    echo "Extracting data...\n";
    exec('tar -zxvf magento-' . $magentoVersion . '.tar.gz',$output);
    //var_dump($output);

    echo "Moving files...\n";
    exec('mv magento/* magento/.htaccess .',$output);
    //var_dump($output);

    echo "Setting permissions...\n";
    exec('chmod o+w var var/.htaccess app/etc');
    exec('chmod -R o+w media');

    echo "Initializing PEAR registry...\n";
    exec('./mage mage-setup .',$output);
    //var_dump($output);

    echo "Downloading packages...\n";
    exec('./mage install magento-core/Mage_All_Latest',$output);
    //var_dump($output);

    echo "Cleaning up files...\n";
    exec('rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*');
    exec('rm -rf magento/ magento-' . $magentoVersion . '.tar.gz');
    exec('rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt');

    echo "Installing Magento...\n";
    exec('php -f install.php --' .
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
            ' --admin_password "' . $adminpass . '"',$output);
    //var_dump($output);

    echo "Finished installing Magento\n";
    
    //TODO: add mail info about ready installation
    
    
    //$update = new Zend_Db_Update($db);
    $db->update('queue',array('status'=>'ready'),'id='.$queueElement['id']);
    
    chdir($startCwd);
    exit;
}