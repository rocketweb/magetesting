<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/'));
define('APPLICATION_ENVIRONMENT', 'development');

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
$autoloader->registerNamespace('My_');
$autoloader->registerNamespace('Db_');


/**
 * Include my complete Bootstrap
 * @todo change when time is left
 */
// Create application, bootstrap, and run
$application = new Zend_Application(
                APPLICATION_ENVIRONMENT,
                APPLICATION_PATH . '/configs/application.ini'
);

$application->getBootstrap()->bootstrap(array('db'));


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

    $bootstrap = $application->getBootstrap();
    $configDb = $bootstrap->getResource('db')->getConfig();
    $configMagento = $bootstrap->getResource('magento')->getConfig();

    $domain = randomString(5);
    $dbhost = $configDb['host']; //fetch from zend config
    $dbname = $configDb['dbname']; //fetch from zend config
    $dbuser = $configDb['user']; //fetch from zend config
    $dbpass = $configDb['pass']; //fetch from zend config
    
    $adminemail = $configMagento['adminEmail']; //fetch from zend config
    $storeurl = $configMagento['storeUrl']; //fetch from zend config
    
    $adminuser = 'admin';
    $adminpass = substr(
                    str_shuffle(
                            str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)
                    )
                    , 0, 8);
    $adminfname = 'Admin';
    $adminlname = 'McAdmin';
    
    $magentoVersion = '1.6.1.0';

    echo "Now installing Magento without sample data...\n";
    echo "Downloading packages...\n";
    exec('wget http://www.magentocommerce.com/downloads/assets/' . $magentoVersion . '/magento-' . $magentoVersion . '.tar.gz');

    echo "Extracting data...\n";
    exec('tar -zxvf magento-' . $magentoVersion . '.tar.gz');

    echo "Moving files...\n";
    exec('mv magento/* magento/.htaccess .');

    echo "Setting permissions...\n";
    exec('chmod o+w var var/.htaccess app/etc');
    exec('chmod -R o+w media');

    echo "Initializing PEAR registry...\n";
    exec('./pear mage-setup .');

    echo "Downloading packages...\n";
    exec('./pear install magento-core/Mage_All_Latest');

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
            ' --db_prefix "' . $domain . '"' .
            ' --url "' . $storeurl . '"' .
            ' --use_rewrites "yes"' .
            ' --use_secure "no"' .
            ' --secure_base_url ""' .
            ' --use_secure_admin "no"' .
            ' --admin_firstname "' . $adminfname . '"' .
            ' --admin_lastname "' . $adminlname . '"' .
            ' --admin_email "' . $adminemail . '"' .
            ' --admin_username "' . $adminuser . '"' .
            ' --admin_password "' . $adminpass . '"');

    echo "Finished installing Magento\n";
    exit;
}