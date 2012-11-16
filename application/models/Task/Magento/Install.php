<?php

class Application_Model_Task_Magento_Install 
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    protected $_adminemail = '';

    
    /* Prevents from running constructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
        $this->filePrefix = $this->_getFilePrefix();
    }
    
    public function setup(Application_Model_Queue $queueElement) {
        parent::setup($queueElement);
        $this->_adminemail = $this->config->magento->adminEmail; //fetch from zend config       
        $this->logger = $this->_getLogger();
        $this->_instanceFolder = $this->_getInstanceFolder();
    }
    
    public function process() {
               
        $this->_updateStatus('installing');
        $this->_createSystemAccount();
        $this->_updateStatus('installing-magento');    
      
        $startCwd = getcwd();
        
        $this->_updateStatus('installing-files');

        $this->_prepareFilesystem();
        
        $this->_setFilesystemPermissions();

        if ($this->_instanceObject->getSampleData()) {
            $this->_updateStatus('installing-samples');
            $this->_installSampleData();
        }

        $this->_updateStatus('installing-magento');

        $this->_cleanupFilesystem();

        $this->_updateStatus('installing-magento');

        $this->_runInstaller();

        $this->_setupMagentoConnect();

        $this->_applyXmlRpcPatch();

        $this->_disableAdminNotifications();

        $this->_createSymlink();
        
        $this->_updateStatus('ready');

        chdir($startCwd);

        //$this->_sendInstanceReadyEmail();
    }

    protected function _prepareFileSystem() {

        $this->_magentoVersion = $this->_versionObject->getVersion();
        $this->_magentoEdition = $this->_instanceObject->getEdition();       
        $this->_sampleDataVersion = $this->_versionObject->getSampleDataVersion();
        
        chdir($this->_instanceFolder);

        if ($this->_instanceObject->getSampleData() && !file_exists(APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz')) {
            $message = 'Couldn\'t find sample data file, will not install queue element';
            $this->_updateStatus('error',$message);
            return false; //jump to next queue element
        }

        //echo "Preparing directory...\n";
        exec('sudo mkdir ' . $this->_instanceFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
                
        $this->logger->log($message, LOG_DEBUG);
        unset($output);

        if (!file_exists($this->_instanceFolder . '/' . $this->_domain) || !is_dir($this->_instanceFolder . '/' . $this->_domain)) {
            $message = 'Directory does not exist, aborting';
            $this->_updateStatus('error',$message);
            $this->logger->log($message, LOG_DEBUG);
            //shouldn't continue be here?
            return false;
        }

        exec('sudo chmod +x ' . $this->_instanceFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
        $this->logger->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);

        chdir($this->_domain);

        //echo "Copying package to target directory...\n";

        if (!file_exists(APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz')) {
            $message = 'Couldn\'t find package files, aborting';
            echo $message;
            $this->_updateStatus('error',$message);
            $this->logger->log($message, LOG_DEBUG);
            return false; //jump to next queue element
        }

        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz ' . $this->_instanceFolder . '/' . $this->_domain . '/', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz ' . $this->_instanceFolder . '/' . $this->_domain . "/\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/keyset0.sql ' . $this->_instanceFolder . '/' . $this->_domain . '/');
        
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/keyset1.sql ' . $this->_instanceFolder . '/' . $this->_domain . '/');

        if ($this->_instanceObject->getSampleData()) {
            $this->_updateStatus('installing-samples');
            //echo "Copying sample data package to target directory...\n";
            exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz ' . $this->_instanceFolder . '/' . $this->_domain . '/', $output);
            $message = var_export($output, true);
            $this->logger->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz ' . $this->_instanceFolder . '/' . $this->_domain . "/\n" . $message, LOG_DEBUG);
            unset($output);
        }

        $this->_updateStatus('installing-files');
        //echo "Extracting data...\n";
        $this->_installFiles();

        //echo "Moving files...\n";
        exec('sudo cp -R magento/* .', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo cp -R magento/* .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp magento/.htaccess .', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo cp magento/.htaccess .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('rm -R ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/magento');
       
    }

    protected function _installFiles() {
        exec('sudo tar -zxvf ' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo tar -zxvf " . $this->filePrefix[$this->_magentoEdition] . "-" . $this->_magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        if ($this->_instanceObject->getSampleData()) {
            $this->_updateStatus('installing-samples');
            //echo "Extracting sample data...\n";
            $command = 'sudo tar -zxvf magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, LOG_DEBUG);
            unset($output);

            //echo "Moving sample data files...\n";
            $command = 'sudo mv magento-sample-data-' . $this->_sampleDataVersion . '/* .';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, LOG_DEBUG);
            unset($output);
        }
    }

    protected function _installSampleData() {
        //echo "Inserting sample data\n";
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' < magento_sample_data_for_' . $this->_sampleDataVersion . '.sql');
    }

    protected function _setFilesystemPermissions() {
         //echo "Setting permissions...\n";
        $command = 'sudo chmod 777 var/.htaccess app/etc';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 var -R', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 downloader', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo sudo chmod 777 downloader\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 media -R', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo chmod -R 777 media\n" . $message, LOG_DEBUG);
        unset($output);
    }

    protected function _cleanupFilesystem() {
        //echo "Cleaning up files...\n";
        exec('sudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf magento/ ' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo rm -rf magento/ " . $this->filePrefix[$this->_magentoEdition] . "-" . $this->_magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt', $output);
        $message = var_export($output, true);
        $this->logger->log("\nsudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n" . $message, LOG_DEBUG);
        unset($output);

        if ($this->_instanceObject->getSampleData()) {
            
            $sampleDataVersion = $this->_versionObject->getSampleDataVersion();
            exec('sudo rm -rf magento-sample-data-' . $sampleDataVersion . '/ magento-sample-data-' . $sampleDataVersion . '.tar.gz magento_sample_data_for_' . $sampleDataVersion . '.sql', $output);
            $message = var_export($output, true);
            $this->logger->log("\nsudo rm -rf magento-sample-data-" . $sampleDataVersion . "/ magento-sample-data-" . $sampleDataVersion . ".tar.gz magento_sample_data_for_" . $sampleDataVersion . ".sql\n" . $message, LOG_DEBUG);
            unset($output);
        }
    }

    protected function _setupMagentoConnect() {
        // create magento connect ftp config and remove settings for free user
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
                'ftp://', 'ftp://' . $this->config->magento->userprefix . $this->_dbuser . ':' . $this->_systempass . '@', $this->config->magento->ftphost
        );

        $connect_cfg = array(
            'php_ini' => '',
            'protocol' => 'http',
            'preferred_state' => 'stable',
            'use_custom_permissions_mode' => '0',
            'global_dir_mode' => 511,
            'global_file_mode' => 438,
            'root_channel_uri' => 'connect20.magentocommerce.com/community',
            'root_channel' => 'community',
            'sync_pear' => false,
            'downloader_path' => 'downloader',
            'magento_root' => $this->_instanceFolder . '/' . $this->_domain,
            'remote_config' => $ftp_user_host . '/public_html/' . $this->_domain
        );

        $free_user = $this->_userObject->getGroup() == 'free-user' ? true : false;
        if ($free_user AND !stristr($this->_magentoVersion, '1.4')) {
            // index.php file
            $index_file = file_get_contents($this->_instanceFolder . '/' . $this->_domain . '/downloader/index.php');
            $new_index_file = str_replace(
                    '<?php', '<?php' . PHP_EOL . '
if(stristr($_SERVER[\'REQUEST_URI\'], \'setting\')) {
    header(\'Location: http://\'.$_SERVER[\'SERVER_NAME\'].$_SERVER[\'PHP_SELF\']);
    exit;
}', $index_file
            );
            file_put_contents(
                    $this->_instanceFolder . '/' . $this->_domain . '/downloader/index.php', $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($this->_instanceFolder . '/' . $this->_domain . '/downloader/template/header.phtml');
            file_put_contents(
                    $this->_instanceFolder . '/' . $this->_domain . '/downloader/template/header.phtml', preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        file_put_contents($this->_instanceFolder . '/' . $this->_domain . '/downloader/connect.cfg', $header . serialize($connect_cfg));
        // end
    }

    protected function _disableAdminNotifications() {
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' -e \'INSERT INTO core_config_data (`scope`,`scope_id`,`path`,`value`) VALUES ("default",0,"advanced/modules_disable_output/Mage_AdminNotification",1) ON DUPLICATE KEY UPDATE `value` = 1\'');
    }

    protected function _runInstaller() {
        //echo "Installing Magento...\n";
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' < keyset0.sql');
        
        $storeurl = $this->config->magento->storeUrl . '/instance/' . $this->_instanceObject->getDomain(); //fetch from zend config
        
        $command = 'cd ' . $this->_instanceFolder . '/' . $this->_domain . ';sudo  /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $this->_dbhost . '"' .
                ' --db_name "' . $this->config->magento->instanceprefix . $this->_dbname . '"' .
                ' --db_user "' . $this->config->magento->userprefix . $this->_dbuser . '"' .
                ' --db_pass "' . $this->_dbpass . '"' .              
                ' --url "' . $storeurl . '"' .
                ' --use_rewrites "yes"' .
                ' --use_secure "no"' .
                ' --secure_base_url ""' .
                ' --use_secure_admin "no"' .
                ' --admin_firstname "' . $this->_adminfname . '"' .
                ' --admin_lastname "' . $this->_adminlname . '"' .
                ' --admin_email "' . $this->_adminemail . '"' .
                ' --admin_username "' . $this->_adminuser . '"' .
                ' --admin_password "' . $this->_adminpass . '"' .
                ' --skip_url_validation "yes"';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n" . $command. "\n" . $message, LOG_DEBUG);
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' < keyset1.sql');
        unset($output);

        $command = 'sudo chown -R ' . $this->config->magento->userprefix . $this->_dbuser . ':' . $this->config->magento->userprefix . $this->_dbuser . ' ' . $this->_instanceFolder . '/' . $this->_domain;
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n" .$command. "\n" . $message, LOG_DEBUG);
        unset($output);

        // update backend admin password
        $set = array('backend_password' => $this->_adminpass);
        $where = array('domain = ?' => $this->_domain);
        $this->logger->log(PHP_EOL . 'Updating queue backend password: ' . $this->db->update('instance', $set, $where), Zend_Log::DEBUG);
        // end
    }

}
