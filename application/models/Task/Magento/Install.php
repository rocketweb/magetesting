<?php

class Application_Model_Task_Magento_Install 
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    protected $_adminemail = '';

    public function setup(Application_Model_Queue $queueElement) {
        parent::setup($queueElement);
        
        /*TODO: check if we inherit this from parent */
        $this->_adminemail = $this->config->magento->adminEmail; //fetch from zend config       
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        
        $startCwd = getcwd();
        
        $this->_updateStoreStatus('installing-magento');
        
        $this->_prepareDatabase();
        
        $this->_createSystemAccount();
              
        $this->_prepareFilesystem();
        
        $this->_installFiles();
        
        $this->_setFilesystemPermissions();

        if ($this->_storeObject->getSampleData()) {
            $this->_installSampleData();
        }

        $this->_cleanupFilesystem();

        $this->_runXmlPatch();
        
        $this->_runInstaller();
        
        /**
         * In i.e 1.7.0.2 locks were created during running _runInstaller, 
         * this is to prevent locking reindexer in adminpanel then
         */
        $dirname = $this->_storeFolder . '/' . $this->_domain.'/var/locks/';
        if (file_exists($dirname) && is_dir($dirname)){
            exec('chmod 666 -R '.$dirname.'*');
        }

        $this->_setupMagentoConnect();

        $this->_applyXmlRpcPatch();

        $this->_updateStoreConfigurationEmails();
        
        $this->_disableAdminNotifications();
        $this->_enableLogging();
      
        chdir($startCwd);

        $this->_reindexStore();
        
        $this->_createLocalXml();
        
        if ($this->_storeObject->getEdition()=='EE'){
            $this->_updateAdminAccount();
        }
        
        $this->_fixUserHomeChmod();
    }

    protected function _prepareFileSystem() {

        $this->_magentoVersion = $this->_versionObject->getVersion();
        $this->_magentoEdition = $this->_storeObject->getEdition();       
        $this->_sampleDataVersion = $this->_versionObject->getSampleDataVersion();
        
        chdir($this->_storeFolder);

        if ($this->_storeObject->getSampleData() && !file_exists(APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz')) {
            $message = 'Couldn\'t find sample data file, will not install queue element';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain)){
            $this->logger->log('Preparing store directory.', Zend_Log::INFO);
            exec('sudo mkdir ' . $this->_storeFolder . '/' . $this->_domain, $output);
            $message = var_export($output, true);

            $this->logger->log($message, Zend_Log::DEBUG);
            unset($output);
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain) || !is_dir($this->_storeFolder . '/' . $this->_domain)) {
            $message = 'Store directory does not exist, aborting.';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }

        $this->logger->log('Changing chmod for domain: ' . $this->_domain, Zend_Log::INFO);
        exec('sudo chmod +x ' . $this->_storeFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);
        unset($output);

        chdir($this->_domain);

        if (!file_exists(APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz')) {
            $message = 'Magento package ' . $this->_magentoEdition . ' ' . $this->_magentoVersion . ' does not exist';
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        }

        $this->logger->log('Copying Magento package to store directory.', Zend_Log::INFO);
        $command = 'sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz ' . $this->_storeFolder . '/' . $this->_domain . '/';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/keyset0.sql ' . $this->_storeFolder . '/' . $this->_domain . '/');
        
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/keyset1.sql ' . $this->_storeFolder . '/' . $this->_domain . '/');

        if ($this->_storeObject->getSampleData()) {
            $this->logger->log('Copying sample data package to target directory.', Zend_Log::INFO);
            $command = 'sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $this->_magentoEdition . '/magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz ' . $this->_storeFolder . '/' . $this->_domain . '/';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            unset($output);
        }
    }

    protected function _installFiles() {
        /**
         * strip-components=1 gets rid of magento folder and unpacks its contents  
         * straight to our store root
         */
        $this->logger->log('Unpacking magento files.', Zend_Log::INFO);
        $command  = 'sudo tar -zxvf ' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz --strip-components=1';
        exec($command, $output);
        $message = var_export($output, true);

        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        if ($this->_storeObject->getSampleData()) {
            $this->logger->log('Extracting sample data.', Zend_Log::INFO);
            $command = 'sudo tar -zxvf magento-sample-data-' . $this->_sampleDataVersion . '.tar.gz';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            unset($output);

            /* Note: we cannot move (mv) here, because media already exists */
            $this->logger->log('Copying sample data files to root.', Zend_Log::INFO);
            $command = 'sudo cp -R magento-sample-data-' . $this->_sampleDataVersion . '/* .';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            
            $this->logger->log('removing sample data files.', Zend_Log::INFO);
            $command = 'rm -R magento-sample-data-' . $this->_sampleDataVersion . '';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            
            unset($output);
        }
    }

    protected function _installSampleData() {
        $this->logger->log('Inserting sample data.', Zend_Log::INFO);

        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' < magento_sample_data_for_' . $this->_sampleDataVersion . '.sql');
    }

    protected function _setFilesystemPermissions() {
        $this->logger->log('Setting store directory permissions.', Zend_Log::INFO);
        $command = 'sudo chmod 777 var/.htaccess app/etc';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = 'sudo chmod 777 var -R';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = 'sudo chmod 777 downloader';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = 'sudo chmod 777 media -R';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);
    }

    protected function _cleanupFilesystem() {
        $this->logger->log('Cleaning up files.', Zend_Log::INFO);
        $command ='sudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = 'sudo rm -rf magento/ ' . $this->filePrefix[$this->_magentoEdition] . '-' . $this->_magentoVersion . '.tar.gz';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = 'sudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        if ($this->_storeObject->getSampleData()) {
            $sampleDataVersion = $this->_versionObject->getSampleDataVersion();
            $command = 'sudo rm -rf magento-sample-data-' . $sampleDataVersion . '/ magento-sample-data-' . $sampleDataVersion . '.tar.gz magento_sample_data_for_' . $sampleDataVersion . '.sql';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
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
            'magento_root' => $this->_storeFolder . '/' . $this->_domain,
            'remote_config' => $ftp_user_host . '/public_html/' . $this->_domain
        );

        $free_user = $this->_userObject->getGroup() == 'free-user' ? true : false;
        if ($free_user AND !stristr($this->_magentoVersion, '1.4')) {
            // index.php file
            $index_file = file_get_contents($this->_storeFolder . '/' . $this->_domain . '/downloader/index.php');
            $new_index_file = str_replace(
                    '<?php', '<?php' . PHP_EOL . '
if(stristr($_SERVER[\'REQUEST_URI\'], \'setting\')) {
    header(\'Location: http://\'.$_SERVER[\'SERVER_NAME\'].$_SERVER[\'PHP_SELF\']);
    exit;
}', $index_file
            );
            file_put_contents(
                    $this->_storeFolder . '/' . $this->_domain . '/downloader/index.php', $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($this->_storeFolder . '/' . $this->_domain . '/downloader/template/header.phtml');
            file_put_contents(
                    $this->_storeFolder . '/' . $this->_domain . '/downloader/template/header.phtml', preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        file_put_contents($this->_storeFolder . '/' . $this->_domain . '/downloader/connect.cfg', $header . serialize($connect_cfg));
        // end
        
        $this->_updateConnectFiles();
    }

    protected function _disableAdminNotifications() {
        $this->logger->log('Disabling admin notifications.', Zend_Log::INFO);

        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e \'INSERT INTO core_config_data (`scope`,`scope_id`,`path`,`value`) VALUES ("default",0,"advanced/modules_disable_output/Mage_AdminNotification",1) ON DUPLICATE KEY UPDATE `value` = 1\'');
    }

    protected function _runInstaller() {
        $this->logger->log('Installing Magento.', Zend_Log::INFO);
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' < keyset0.sql');
        
        
        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());
        
        $storeurl = 'http://'.$this->_dbuser.'.'.$serverModel->getDomain() . '/' . $this->_storeObject->getDomain(); //fetch from zend config
        
        $command = 'cd ' . $this->_storeFolder . '/' . $this->_domain . ';sudo  /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $this->_dbhost . '"' .
                ' --db_name "' . $this->config->magento->storeprefix . $this->_dbname . '"' .
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
        $this->logger->log("\n" . $command. "\n" . $message, Zend_Log::DEBUG);
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' < keyset1.sql');
        unset($output);

        $this->logger->log('Changing owner of store directory.', Zend_Log::INFO);
        $command = 'sudo chown -R ' . $this->config->magento->userprefix . $this->_dbuser . ':' . $this->config->magento->userprefix . $this->_dbuser . ' ' . $this->_storeFolder . '/' . $this->_domain;
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n" .$command. "\n" . $message, Zend_Log::DEBUG);
        unset($output);

        // update backend admin password
        $this->logger->log('Changing store backend password.', Zend_Log::INFO);
        $this->_storeObject->setBackendPassword($this->_adminpass)->save();
        $this->logger->log('Store backend password changed to : ' . $this->_adminpass, Zend_Log::DEBUG);
        // end
        
        exec('sudo rm keyset0.sql keyset1.sql');
    }
    
    /**
     * It happens that in some library versions the installer may come up with error:
     * ERROR: PHP Extensions "0" must be loaded.
     * This code prevents it from happening
     */
    protected function _runXmlPatch(){
        
        $configXml = file_get_contents($this->_storeFolder . '/' . $this->_domain.'/app/code/core/Mage/Install/etc/config.xml');
        $configXml = str_replace('<pdo_mysql/>','<pdo_mysql>1</pdo_mysql>',$configXml);
        file_put_contents($this->_storeFolder .'/'. $this->_domain.'/app/code/core/Mage/Install/etc/config.xml',$configXml);
        unset($configXml);
        
    }
    
    protected function _createLocalXml() {
        $localXmlPath = $this->_storeFolder . '/' . $this->_domain.'/app/design/adminhtml/default/default/layout/local.xml';
        $data = '<layout>
                   <default>
                        <remove name="notification_security" />
                    </default>
                 </layout>';

        if (file_exists($localXmlPath)) {
            $layout = file_get_contents($localXmlPath);
            if(preg_match('/\<default\>/i', $layout)) {
                $data = preg_replace('/\<default\>/i', '<default> 
                        <remove name="notification_security" />', $layout);
            } elseif(preg_match('/\<layout\>/i', $layout)) {
                $data = preg_replace('/\<layout\>/i', '<layout>
                    <default> 
                        <remove name="notification_security" /> 
                    </default> ', $layout);
            } else {
                $data = '<?xml version="1.0"?>' . $data;
            }
        } else {
            $data = '<?xml version="1.0"?>' . $data;
        }
        
        file_put_contents($localXmlPath, $data);
    }
    
    protected function _updateAdminAccount() {
        $timestamp = strtotime('+1 year');
        
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname 
                . ' -e \'UPDATE enterprise_admin_passwords SET expires = \''.$timestamp.'\' \'');
    }
    
    protected function _fixUserHomeChmod(){
        /**
         * This line is here to prevent:
         * 500 OOPS: vsftpd: refusing to run with writable root inside chroot ()
         * when vsftpd is set to use chroot list
         */
        exec('sudo chmod a-w '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin().'');
    }
    
}
