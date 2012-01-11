<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application/'));
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
 * Action : magento-install
 */
if (isset($opts->magentoinstall)) {

    fwrite(STDOUT, "Do you have your database installed ?(y/n)\n");
    $dbinfo = trim(fgets(STDIN));

    if ($dbinfo == "y") {


        fwrite(STDOUT, "Enter database host and press Enter:\n");
        $dbhost = trim(fgets(STDIN));

        fwrite(STDOUT, "Enter database name and press Enter:\n");
        $dbname = trim(fgets(STDIN));

        fwrite(STDOUT, "Enter database username and press Enter:\n");
        $dbuser = trim(fgets(STDIN));

        fwrite(STDOUT, "Enter database password and press Enter:\n");
        $dbpass = trim(fgets(STDIN));

        fwrite(STDOUT, "Enter store url (with trailing slash) Enter:\n");
        $url = trim(fgets(STDIN));
        
        echo "Admin Username: \n";
        $adminuser = trim(fgets(STDIN));

        echo "Admin Password (minimum 7 characters): \n";
        $adminpass = trim(fgets(STDIN));

        echo "Admin First Name: \n";
        $adminfname = trim(fgets(STDIN));

        echo "Admin Last Name: \n";
        $adminlname = trim(fgets(STDIN));

        echo "Admin Email Address: \n";
        $adminemail = trim(fgets(STDIN));

        fwrite(STDOUT, "Include sample data?(y/n):\n");
        $sample = trim(fgets(STDIN));

        if ($sample == "y") {

            echo "Now installing Magento with sample data...\n";
            echo "Downloading packages...\n";

            exec('wget http://www.magentocommerce.com/downloads/assets/1.6.1.0/magento-1.6.1.0.tar.gz');
            exec('wget http://www.magentocommerce.com/downloads/assets/1.6.1.0/magento-sample-data-1.6.1.0.tar.gz');


            echo "Extracting data...\n";
            exec('tar -zxvf magento-1.6.1.0.tar.gz');
            exec('tar -zxvf magento-sample-data-1.6.1.0.tar.gz');

            echo "Moving files...\n";
            exec('mv magento-sample-data-1.6.1.0/media/* magento/media/');
            exec('mv magento-sample-data-1.6.1.0/magento_sample_data_for_1.6.1.0.sql magento/data.sql');
            exec('mv magento/* magento/.htaccess .');

            echo "Setting permissions...\n";
            exec('chmod o+w var var/.htaccess app/etc');
            exec('chmod -R o+w media');


            echo "Importing sample products...\n";
            exec('mysql -h '.$dbhost.' -u '.$dbuser.' -p'.$dbpass.' '.$dbname.' < data.sql');

            echo "Initializing PEAR registry...\n";

            exec('pear mage-setup .');


            echo "Downloading packages...\n";
            exec('pear install magento-core/Mage_All_Latest');

            echo "Cleaning up files...\n";
            exec('rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*');
            exec('rm -rf magento/ magento-sample-data-1.6.1.0/');
            exec('rm -rf magento-1.6.1.0.tar.gz magento-sample-data-1.6.1.0.tar.gz data.sql');
            exec('rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt data.sql');

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
                    ' --url "' . $url . '"' .
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
        } else {
            echo "Now installing Magento without sample data...\n";
            echo "Downloading packages...\n";
            exec('wget http://www.magentocommerce.com/downloads/assets/1.6.1.0/magento-1.6.1.0.tar.gz');


            echo "Extracting data...\n";
            exec('tar -zxvf magento-1.6.1.0.tar.gz');


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
            exec('rm -rf magento/ magento-1.6.1.0.tar.gz');
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
                    ' --url "' . $url . '"' .
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
    } else {
        echo "Please setup a database first. Don't forget to assign a database user!";
        exit;
    }
}




