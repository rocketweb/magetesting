<?php

class Application_Model_Task {

    public function __construct($config,$db) {
        $this->config = $config;
        $this->db = $db;
        $this->filePrefix = array(
            'CE' => 'magento',
            'EE' => 'enterprise',
            'PE' => 'professional',
        );
    }
    
    /**
     * Create DB,unpack files and prepare instance
     */
    public function magentoInstall(Application_Model_Queue $queueElement)
    {
        $userModel = new Application_Model_User();
        $instanceOwner = $userModel->find($queueElement->getUserId());
        
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
                
        //drop database
        $dbname = $instanceOwner->getLogin().'_'.$instanceModel->getDomain();
        
        $this->db->update('queue', array('status' => 'installing'), 'instance_id=' . $queueElement->getInstanceId());
        $this->db->update('instance', array('status' => 'installing'), 'id=' . $queueElement->getInstanceId());

        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $instanceOwner->getLogin() . '_' . $instanceModel->getDomain() . '.log');
        $log = new Zend_Log($writer);

        $dbhost = $this->config->resources->db->params->host; //fetch from zend config
        $dbname = $instanceOwner->getLogin() . '_' . $instanceModel->getDomain();
        $dbuser = $instanceOwner->getLogin(); 
        $dbpass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $instanceOwner->getLogin()), 0, 10); 
        
        $instanceFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $dbuser . '/public_html';
        
        $systempass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $instanceOwner->getLogin()), 10, 10); 
        if ($instanceOwner->getHasSystemAccount() == 0) {
            $this->db->update('queue', array('status' => 'installing-user'), 'instance_id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('status' => 'installing-user'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('user', array('system_account_name' => $this->config->magento->userprefix . $dbuser), 'id=' . $instanceOwner->getId());

            /** WARNING!
             * in order for this to work, when you run this (console.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php worker.php
             *
             */
            exec('sudo ./create_user.sh ' . $this->config->magento->userprefix . $dbuser . ' ' . $systempass . ' ' . $this->config->magento->usersalt . ' ' . $this->config->magento->systemHomeFolder, $output);
            $message = var_export($output, true);
            $log->log($message, LOG_DEBUG);
            unset($output);

            //TODO: Move this logic somewhere else, 
            // we're going tu use plan update for this
            //
            //TODO: check if $queueElement['plan_id'] has access 
            // to ftp account and send over credentials
            
            if('free-user' != $instanceOwner->getGroup()) {
                /* send email with account details start */
                $modelUser = new Application_Model_User();
                $user_details = array(
                    'dbuser' => $dbuser,
                    'dbpass' => $dbpass,
                    'systempass' => $systempass,
                    'email' => $instanceOwner->getEmail(),
                );
                $modelUser->sendFtpEmail($this->config,$user_details);
                $modelUser->sendPhpmyadminEmail($this->config,$user_details);
                /* send email with account details stop */
            }
        }
        
        $this->db->update('queue', array('status' => 'installing-magento'), 'instance_id=' .$queueElement->getInstanceId());
        $this->db->update('instance', array('status' => 'installing-magento'), 'id=' . $queueElement->getInstanceId());
        
        $adminuser = $instanceOwner->getLogin();
        $adminpass = substr(
                        str_shuffle(
                                str_repeat('0123456789', 5)
                        )
                        , 0, 5) . substr(
                        str_shuffle(
                                str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 7)
                        )
                        , 0, 7);

        $adminfname = $instanceOwner->getFirstname();
        $adminlname = $instanceOwner->getLastname();

        //TODO: need to redownload version here!!!
        $versionModel = new Application_Model_Version();
        $versionModel->find($instanceModel->getVersionId());
        $magentoVersion = $versionModel->getVersion();
        $magentoEdition = $versionModel->getEdition();
        $sampleDataVersion = $versionModel->getSampleDataVersion();
        
        $installSampleData = $instanceModel->getSampleData();

        $domain = $instanceModel->getDomain();
        $startCwd = getcwd();

        $message = 'domain: ' . $domain;
        $log->log($message, LOG_DEBUG);

        $dbprefix = $domain . '_';

        $adminemail = $this->config->magento->adminEmail; //fetch from zend config
        $storeurl = $this->config->magento->storeUrl . '/instance/' . $domain; //fetch from zend config
        $message = 'store url: ' . $storeurl;
        $log->log($message, LOG_DEBUG);

        chdir($instanceFolder);

        if ($installSampleData && !file_exists(APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz')){
            $message = 'Couldn\'t find sample data file, will not install queue element';
            //echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            return false; //jump to next queue element
        }
        
        if ($installSampleData) {
            echo "Now installing Magento with sample data...\n";
        } else {
            echo "Now installing Magento without sample data...\n";
        }

        echo "Preparing directory...\n";
        exec('sudo mkdir ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log($message, LOG_DEBUG);
        unset($output);

        if (!file_exists($instanceFolder . '/' . $domain) || !is_dir($instanceFolder . '/' . $domain)) {
            $message = 'Directory does not exist, aborting';
            echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            //shouldn't continue be here?
            return false;
        }

        exec('sudo chmod +x ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);

        chdir($domain);

        echo "Copying package to target directory...\n";
        
        if (!file_exists(APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/'.$this->filePrefix[$magentoEdition].'-' . $magentoVersion . '.tar.gz')){
            $message = 'Couldn\'t find package files, aborting';
            echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            return false; //jump to next queue element
        }
        
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/'.$this->filePrefix[$magentoEdition].'-' . $magentoVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . '/', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/'.$this->filePrefix[$magentoEdition].'-' . $magentoVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . "/\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/keyset0.sql ' . $instanceFolder . '/' . $domain . '/');
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/keyset1.sql ' . $instanceFolder . '/' . $domain . '/');

        if ($installSampleData) {
            $this->db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'installing-samples'), 'id=' . $queueElement->getInstanceId());
            echo "Copying sample data package to target directory...\n";
            exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' .$magentoEdition . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . '/', $output);
            $message = var_export($output, true);
            $log->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $magentoEdition . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . "/\n" . $message, LOG_DEBUG);
            unset($output);
        }

       
        $this->db->update('queue', array('status' => 'installing-files'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-files'), 'id=' . $queueElement->getInstanceId());
        echo "Extracting data...\n";
        exec('sudo tar -zxvf '.$this->filePrefix[$magentoEdition].'-' . $magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $log->log("\nsudo tar -zxvf ".$this->filePrefix[$magentoEdition]."-" . $magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {
            $this->db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'installing-samples'), 'id=' . $queueElement->getInstanceId());
            echo "Extracting sample data...\n";
            exec('sudo tar -zxvf magento-sample-data-' . $sampleDataVersion . '.tar.gz', $output);
            $message = var_export($output, true);
            $log->log("\nsudo tar -zxvf magento-sample-data-" . $sampleDataVersion . ".tar.gz\n" . $message, LOG_DEBUG);
            unset($output);

            echo "Moving sample data files...\n";
            exec('sudo mv magento-sample-data-' . $sampleDataVersion . '/* .', $output);
            $message = var_export($output, true);
            $log->log("\nsudo mv mv magento-sample-data-" . $sampleDataVersion . "/* .\n" . $message, LOG_DEBUG);
            unset($output);
        }

        $this->db->update('queue', array('status' => 'installing-files'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-files'), 'id=' . $queueElement->getInstanceId());
        echo "Moving files...\n";
        exec('sudo cp -R magento/* .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp -R magento/* .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp magento/.htaccess .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp magento/.htaccess .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('rm -R ' . $instanceFolder . '/' . $domain . '/magento');

        echo "Setting permissions...\n";
        exec('sudo chmod 777 var/.htaccess app/etc', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 var -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);
        
        exec('sudo chmod 777 downloader', $output);
        $message = var_export($output, true);
        $log->log("\nsudo sudo chmod 777 downloader\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 media -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod -R 777 media\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {          
            $this->db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'installing-samples'), 'id=' . $queueElement->getInstanceId());
            echo "Inserting sample data\n";
            exec('sudo mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' < magento_sample_data_for_' . $sampleDataVersion . '.sql');
        }
        
        $this->db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-magento'), 'id=' . $queueElement->getInstanceId());
        echo "Cleaning up files...\n";
        exec('sudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf magento/ '.$this->filePrefix[$magentoEdition].'-' . $magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf magento/ ".$this->filePrefix[$magentoEdition]."-" . $magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {
            exec('sudo rm -rf magento-sample-data-' . $sampleDataVersion . '/ magento-sample-data-' . $sampleDataVersion . '.tar.gz magento_sample_data_for_' . $sampleDataVersion . '.sql', $output);
            $message = var_export($output, true);
            $log->log("\nsudo rm -rf magento-sample-data-" . $sampleDataVersion . "/ magento-sample-data-" . $sampleDataVersion . ".tar.gz magento_sample_data_for_" . $sampleDataVersion . ".sql\n" . $message, LOG_DEBUG);
            unset($output);
        }
        $this->db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-magento'), 'id=' . $queueElement->getInstanceId());
        echo "Installing Magento...\n";
        exec('sudo mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' < keyset0.sql');
        exec('cd ' . $instanceFolder . '/' . $domain . ';sudo  /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $dbhost . '"' .
                ' --db_name "' . $this->config->magento->instanceprefix . $dbname . '"' .
                ' --db_user "' . $this->config->magento->userprefix . $dbuser . '"' .
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
                ' --skip_url_validation "yes"', $output);
        exec('sudo mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' < keyset1.sql');
        // update backend admin password
        $set = array('backend_password' => $adminpass);
        $where = array('domain = ?' => $domain);
        $log->log(PHP_EOL . 'Updating queue backend password: ' . $this->db->update('instance', $set, $where), Zend_Log::DEBUG);
        // end
        // create magento connect ftp config and remove settings for free user
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
            'ftp://',
            'ftp://'.$this->config->magento->userprefix.$dbuser.':'.$systempass.'@',
            $this->config->magento->ftphost
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
                'magento_root' => $instanceFolder.'/'.$domain,
                'remote_config' => $ftp_user_host.'/public_html/'.$domain
        );
        $free_user = $instanceOwner->getGroup() == 'free-user' ? true : false;
        if($free_user AND !stristr($magentoVersion, '1.4')) {
            // index.php file
            $index_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/index.php');
            $new_index_file = str_replace(
                '<?php',
                '<?php'.PHP_EOL.'
if(stristr($_SERVER[\'REQUEST_URI\'], \'setting\')) {
    header(\'Location: http://\'.$_SERVER[\'SERVER_NAME\'].$_SERVER[\'PHP_SELF\']);
    exit;
}',
                $index_file
            );
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/index.php',
                $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/template/header.phtml');
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/template/header.phtml',
                    preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        file_put_contents($instanceFolder.'/'.$domain.'/downloader/connect.cfg', $header.serialize($connect_cfg));
        // end
        $message = var_export($output, true);
        $log->log("\n" . 'cd ' . $instanceFolder . '/' . $domain . ';sudo /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $dbhost . '"' .
                ' --db_name "' . $this->config->magento->instanceprefix . $dbname . '"' .
                ' --db_user "' . $this->config->magento->userprefix . $dbuser . '"' .
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
                ' --skip_url_validation "yes"' . "\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chown -R '.$this->config->magento->userprefix.$dbuser.':'.$this->config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain, $output);
        $message = var_export($output, true);
        $log->log("\nsudo chown -R ".$this->config->magento->userprefix.$dbuser.':'.$this->config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain."\n" . $message, LOG_DEBUG);
        unset($output);
        
        
        //applying patches for xml-rpc issue
        if ($magentoVersion > '1.3.2.3' AND $magentoVersion < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($magentoVersion == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($magentoVersion > '1.4.9.9' AND $magentoVersion < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
        }
        
        
        echo "Finished installing Magento\n";

        //disable adminnotifications output
        exec('mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' -e \'INSERT INTO core_config_data (`scope`,`scope_id`,`path`,`value`) VALUES ("default",0,"advanced/modules_disable_output/Mage_AdminNotification",1) ON DUPLICATE KEY UPDATE `value` = 1\'');
        
        //TODO: add mail info about ready installation
        
        exec('ln -s ' . $instanceFolder . '/' . $domain . ' ' . INSTANCE_PATH . $domain);

        $this->db->update('queue', array('status' => 'ready'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'ready'), 'id=' . $queueElement->getInstanceId());

        chdir($startCwd);

        /* send email to instance owner start */
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
// assign valeues
        $html->assign('domain', $domain);
        $html->assign('storeUrl', $this->config->magento->storeUrl);
        $html->assign('admin_login', $adminuser);
        $html->assign('admin_password', $adminpass);
// render view
        $bodyText = $html->render('queue-item-ready.phtml');

// create mail object
        $mail = new Zend_Mail('utf-8');
// configure base stuff
        $mail->addTo($instanceOwner->getEmail());
        $mail->setSubject($this->config->cron->queueItemReady->subject);
        $mail->setFrom($this->config->cron->queueItemReady->from->email, $this->config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
        $mail->send();
        /* send email to instance owner stop */
    }
    
    /**
     * Download magento data from Client Server using
     * custom_* columns from DB
     */
    public function magentoDownload(Application_Model_Queue $queueElement){      
        
        $userModel = new Application_Model_User();
        $instanceOwner = $userModel->find($queueElement->getUserId());
        
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
        
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $instanceOwner->getLogin() . '_' . $instanceModel->getDomain() . '.log');
        $log = new Zend_Log($writer);
        
        
        $sqlFileLimit = '60000000'; // In Bytes!
                    
        $this->db->update('queue', array('status' => 'installing'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing'), 'id=' . $queueElement->getInstanceId());
 
        $dbhost = $this->config->resources->db->params->host; //fetch from zend config
        $dbname = $instanceOwner->getLogin() . '_' . $instanceModel->getDomain();
        $dbuser = $instanceOwner->getLogin(); //fetch from zend config
        $dbpass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $instanceOwner->getLogin()), 0, 10); //fetch from zend config
        $instanceFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $dbuser . '/public_html';

        if ($instanceOwner->getHasSystemAccount() == 0) {
            $this->db->update('queue', array('status' => 'installing-user'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'installing-user'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('user', array('system_account_name' => $this->config->magento->userprefix . $dbuser), 'id=' . $instanceOwner->getId());

            /** WARNING!
             * in order for this to work, when you run this (worker.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php worker.php
             *
             */
            exec('sudo ./create_user.sh ' . $this->config->magento->userprefix . $dbuser . ' ' . $dbpass . ' ' . $this->config->magento->usersalt . ' ' . $this->config->magento->systemHomeFolder, $output);
            $message = var_export($output, true);
            $log->log($message, LOG_DEBUG);
            unset($output);

            /**
             * TODO: move to user model?
             */
            if('free-user' != $instanceOwner->getGroup()) {
                $userModel->sendSystemAccountEmail($this->config,$user_details);
            
                /* send email with account details start */
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
                // assign valeues
                $html->assign('ftphost', $this->config->magento->ftphost);
                $html->assign('ftpuser', $this->config->magento->userprefix . $dbuser);
                $html->assign('ftppass', $dbpass);

                $html->assign('dbhost', $this->config->magento->dbhost);
                $html->assign('dbuser', $this->config->magento->userprefix . $dbuser);
                $html->assign('dbpass', $dbpass);

                $html->assign('storeUrl', $this->config->magento->storeUrl);

                // render view
                $bodyText = $html->render('system-account-created.phtml');

                // create mail object
                $mail = new Zend_Mail('utf-8');
                // configure base stuff
                $mail->addTo($instanceOwner->getEmail());
                $mail->setSubject($this->config->cron->systemAccountCreated->subject);
                $mail->setFrom($this->config->cron->systemAccountCreated->from->email, $this->config->cron->systemAccountCreated->from->desc);
                $mail->setBodyHtml($bodyText);
                $mail->send();
                /* send email with account details stop */
            }
        }
        $this->db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-magento'), 'id=' . $queueElement->getInstanceId());
        $adminuser = $instanceOwner->getLogin();
        $adminpass = substr(
                        str_shuffle(
                                str_repeat('0123456789', 5)
                        )
        , 0, 5) . substr(
        str_shuffle(
                str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 7)
        )
        , 0, 7);

        $adminfname = $instanceOwner->getFirstname();
        $adminlname = $instanceOwner->getLastname();

        $versionModel = new Application_Model_Version();
        $versionModel->find($instanceModel->getVersionId());
        $magentoVersion = $versionModel->getVersion();
        $magentoEdition = $versionModel->getEdition();
        $sampleDataVersion = $versionModel->getSampleDataVersion();
        
        
        $installSampleData = $instanceModel->getSampleData();

        $domain = $instanceModel->getDomain();

        $startCwd = getcwd();

        $message = 'domain: ' . $domain;
        $log->log($message, LOG_DEBUG);

        $dbprefix = $domain . '_';

        $adminemail = $this->config->magento->adminEmail; //fetch from zend config
        $storeurl = $this->config->magento->storeUrl . '/instance/' . $domain; //fetch from zend config
        $message = 'store url: ' . $storeurl;
        $log->log($message, LOG_DEBUG);

        chdir($instanceFolder);

        //fetch sql file or yield error when not found

        //HOST
        $customHost = $instanceModel->getCustomHost();
        //make sure custom host have slash at the end
        if(substr($customHost,-1)!="/"){
            $customHost .= '/';
        }

        //PATH
        $customRemotePath = $instanceModel->getCustomRemotePath();
        //make sure remote path containts slash at the end
        if(substr($customRemotePath,-1)!="/"){
            $customRemotePath .= '/';
        }

        //make sure remote path does not contain slash at the beginning       
        if(substr($customRemotePath,0,1)=="/"){
            $customRemotePath = substr($customRemotePath,1);
        }

        //make sure remote path contains prefix:
        if ($instanceModel->getCustomProtocol()=='ftp'){
            if(substr($customHost, 0, 6)!='ftp://'){
                $customHost = 'ftp://'.$customHost;
            }
        }
        
        //FILE
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $instanceModel->getCustomSql();
        if(substr($customSql,0,1)=="/"){
            $customSql = substr($customSql,1);
        }

        //do a sample connection to wget to check if protocol credentials are ok
        exec("wget --spider ".$customHost." ".
             "--passive-ftp ".
             "--user='".$instanceModel->getCustomLogin()."' ".
             "--password='".$instanceModel->getCustomPass()."' ".
             "".$customHost." 2>&1 | grep 'Logged in!'",$output);

        $this->db->update('queue', array('status' => 'installing-data'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-data'), 'id=' . $queueElement->getInstanceId());
        
        if (!isset($output[0])){
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => 'Protocol credentials does not match'), 'id=' . $queueElement->getInstanceId());
            $log->log("Protocol credentials does not match\n", LOG_DEBUG);
        }

        //connect through wget
        exec("wget --spider ".$customHost.$customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$instanceModel->getCustomLogin()."' ".
            "--password='".$instanceModel->getCustomPass()."' ".
            "".$customHost.$customRemotePath." | grep 'SIZE'",$output);

        $message = var_export($output, true);
        $log->log("wget --spider ".$customHost.$customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$instanceModel->getCustomLogin()."' ".
            "--password='".$instanceModel->getCustomPass()."' ".
            "".$customHost.$customRemotePath." | grep 'SIZE'\n" . $message, LOG_DEBUG);

        foreach ($output as $out) {
            $log->log(substr($out, 0, 8), LOG_DEBUG);

            if (substr($out, 0, 8) == '==> SIZE') {
                $sqlSizeInfo = explode(' ... ', $out);
            }
        }

        if(isset($sqlSizeInfo[1])){
            $log->log($sqlSizeInfo[1], LOG_DEBUG);
        }

       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){   
            
            $message = 'Couldn\'t find sql data file, will not install queue element';
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            
            return false;
        }
        unset($output);

        if ($sqlSizeInfo[1] > $sqlFileLimit){
            $message = 'Sql file is too big, aborting';
            //echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            return false; //jump to next queue element
        }

        echo "Preparing directory...\n";
        exec('sudo mkdir ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log($message, LOG_DEBUG);
        unset($output);

        if (!file_exists($instanceFolder . '/' . $domain) || !is_dir($instanceFolder . '/' . $domain)) {
            $message = 'Directory does not exist, aborting';
            echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
        }

        exec('sudo chmod +x ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);

        chdir($domain);
        $this->db->update('queue', array('status' => 'installing-files'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-files'), 'id=' . $queueElement->getInstanceId());
        echo "Copying package to target directory...\n";
        //do a sample connection, and check for index.php, if it works, start fetching
        $command = "wget --spider ".$customHost.$customRemotePath."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$instanceModel->getCustomLogin()."' ".
            "--password='".$instanceModel->getCustomPass()."' ".
            "".$customHost.$customRemotePath." | grep 'SIZE'";
        exec($command, $output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);

        $sqlSizeInfo = explode(' ... ',$output[0]);

       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){
            $message = 'Couldn\'t find app/Mage.php file data, will not install queue element';
            //echo $message;
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            $log->log($message, LOG_DEBUG);
            return false; //jump to next queue element
        }
        unset($output);

        $command = "wget ".
             "--passive-ftp ".
             "-nH ".
             "-Q300m ".
             "-m ".
             "-np ".
             "-R 'sql,tar,gz,zip,rar' ".
             "-X '.htaccess' " . 
             "-I '".$customRemotePath."app,".$customRemotePath."downloader,".$customRemotePath."errors,".$customRemotePath."includes,".$customRemotePath."js,".$customRemotePath."lib,".$customRemotePath."pkginfo,".$customRemotePath."shell,".$customRemotePath."skin' " .
             "--user='".$instanceModel->getCustomLogin()."' ".
             "--password='".$instanceModel->getCustomPass()."' ".
             "".$customHost.$customRemotePath."";
        exec($command, $output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);

        
        $this->db->update('queue', array('status' => 'installing-data'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-data'), 'id=' . $queueElement->getInstanceId());
        
        $command = "wget  ".$customHost.$customSql." ".
            "--passive-ftp ".
            "--user='".$instanceModel->getCustomLogin()."' ".
            "--password='".$instanceModel->getCustomPass()."' ".
            "".$customHost.$customRemotePath." ";
        exec($command,$output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);

        
        $path_parts = pathinfo($customSql);
        //let's load sql to mysql database
        exec('sudo mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' < '.$path_parts['basename'].'');

        $this->db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-magento'), 'id=' . $queueElement->getInstanceId());
        
        echo "Moving downloaded sources to main folder...\n";
        exec('sudo mv '.$customRemotePath.'* .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo mv ".$customRemotePath."* .\n" . $message, LOG_DEBUG);
        unset($output);


        //now lets configure our local xml file
        $connectionString = '<connection>
                    <host><![CDATA[localhost]]></host>
                    <username><![CDATA['.$config->magento->userprefix . $dbuser.']]></username>
                    <password><![CDATA['.$dbpass.']]></password>
                    <dbname><![CDATA['.$config->magento->instanceprefix . $dbname.']]></dbname>
                    <active>1</active>
                </connection>';

        $localXml = file_get_contents($instanceFolder . '/' . $domain.'/app/etc/local.xml');
        $localXml = preg_replace("#<connection>(.*?)</connection>#is",$connectionString,$localXml);
        file_put_contents($instanceFolder .'/'. $domain.'/app/etc/local.xml',$localXml);
        unset($localXml);

        //remove main fetched folder
        $parts = explode('/',$customRemotePath);
        exec('sudo rm '.$parts.' -R', $output);
        unset($parts);

        echo "Setting permissions...\n";
        exec('sudo mkdir var');
        exec('sudo mkdir downloader');
        exec('sudo mkdir media');

        exec('sudo chmod 777 var/.htaccess app/etc', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 var -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);


        exec('sudo chmod 777 downloader', $output);
        $message = var_export($output, true);
        $log->log("\nsudo sudo chmod 777 downloader\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 media -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod -R 777 media\n" . $message, LOG_DEBUG);
        unset($output);

        // update backend admin password
        $set = array('backend_password' => $adminpass);
        $where = array('domain = ?' => $domain);
        $log->log(PHP_EOL . 'Updating queue backend password: ' . $this->db->update('queue', $set, $where), Zend_Log::DEBUG);
        // end
        // create magento connect ftp config and remove settings for free user
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
            'ftp://',
            'ftp://'.$config->magento->userprefix.$dbuser.':'.$dbpass.'@',
            $config->magento->ftphost
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
            'magento_root' => $instanceFolder.'/'.$domain,
            'remote_config' => $ftp_user_host.'/public_html/'.$domain
        );
        $free_user = $instanceOwner->getGroup() == 'free-user' ? true : false;
        if($free_user AND !stristr($magentoVersion, '1.4')) {
            // index.php file
            $index_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/index.php');
            $new_index_file = str_replace(
                            '<?php',
                            '<?php'.PHP_EOL.'
            if(stristr($_SERVER[\'REQUEST_URI\'], \'setting\')) {
                header(\'Location: http://\'.$_SERVER[\'SERVER_NAME\'].$_SERVER[\'PHP_SELF\']);
                exit;
            }',
                            $index_file
            );
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/index.php',
                $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/template/header.phtml');
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/template/header.phtml',
                    preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        
        file_put_contents($instanceFolder.'/'.$domain.'/downloader/connect.cfg', $header.serialize($connect_cfg));
        // end

        //copy new htaccess over
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/Custom/.htaccess ' . $instanceFolder . '/' . $domain . '/.htaccess');

        //applying patches for xml-rpc issue
        if ($magentoVersion > '1.3.2.3' AND $magentoVersion < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');

        } elseif ($magentoVersion == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');

        } elseif ($magentoVersion > '1.4.9.9' AND $magentoVersion < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
        }

        exec('sudo chown -R '.$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain, $output);
        $message = var_export($output, true);
        $log->log("\nsudo chown -R ".$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain."\n" . $message, LOG_DEBUG);
        unset($output);

        //update core_config_data with new url
        exec('mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' -e "UPDATE core_config_data SET value = \''.$this->config->magento->storeUrl.'/instance/'.$domain.'/\' WHERE path=\'web/unsecure/base_url\'"');
        exec('mysql -u' . $this->config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $this->config->magento->instanceprefix . $dbname . ' -e "UPDATE core_config_data SET value = \''.$this->config->magento->storeUrl.'/instance/'.$domain.'/\' WHERE path=\'web/secure/base_url\'"');

        echo "Finished installing Magento\n";

        //TODO: add mail info about ready installation
        exec('ln -s ' . $instanceFolder . '/' . $domain . ' '.INSTANCE_PATH . $domain);
        $log->log(PHP_EOL . 'ln -s ' . $instanceFolder . '/' . $domain . ' '. INSTANCE_PATH . $domain, Zend_Log::DEBUG);
        $this->db->update('queue', array('status' => 'ready'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'ready'), 'id=' . $queueElement->getInstanceId());

        chdir($startCwd);

        /* send email to instance owner start */
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
        $mail->addTo($instanceOwner->getEmail());
        $mail->setSubject($config->cron->queueItemReady->subject);
        $mail->setFrom($config->cron->queueItemReady->from->email, $config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
        $mail->send();
        /* send email to instance owner stop */

        //fetch custom instances fetch
        flock($fp, LOCK_UN); // release the lock      
        
    }
    
    /**
     * Remove instance, its files and DB
     */
    public function magentoRemove(Application_Model_Queue $queueElement)
    {

        $userModel = new Application_Model_User();
        $instanceOwner = $userModel->find($queueElement->getUserId());
        
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
         
        //drop database
        $dbname = $instanceOwner->getLogin().'_'.$instanceModel->getDomain();

        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/'.$instanceOwner->getLogin().'_'.$instanceModel->getDomain().'.log');
        $log = new Zend_Log($writer);

        
$DbManager = new Application_Model_DbTable_Privilege($this->db,$this->config);
        if ($DbManager->checkIfDatabaseExists($dbname)){
            try{
                echo 'trying to drop '.$dbname;
                $DbManager->dropDatabase($dbname);
            } catch(PDOException $e){
                $message = 'Could not remove database for instance';
                echo $message;
                $log->log($message, LOG_ERR);
                flock($fp, LOCK_UN); // release the lock
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

        $instanceFolder = $this->config->magento->systemHomeFolder.'/'.$this->config->magento->userprefix.$instanceOwner->getLogin().'/public_html/'.$instanceModel->getDomain();
        exec('rm -R '.$instanceFolder);
        unlink($instanceModel->getDomain());
        chdir($startCwd);

        $this->db->getConnection()->exec("use ".$this->config->resources->db->params->dbname);

        //remove dev_extension_queue elements for removed queue
        $this->db->delete('dev_extension_queue','instance_id='.$queueElement->getId());
       
        //remove instance extensions
        $this->db->delete('instance_extension','instance_id='.$queueElement->getInstanceId());
        
        //remove this queue element
        $this->db->delete('queue','id='.$queueElement->getId());
        
        //remove any other queue elements related to this instance              
        $this->db->delete('queue','instance_id='.$queueElement->getInstanceId());
        
        //remove instance
        $this->db->delete('instance','id='.$queueElement->getInstanceId());
        
        unlink(APPLICATION_PATH . '/../data/logs/'.$instanceOwner->getLogin().'_'.$instanceModel->getDomain().'.log');
    }
    
    /**
     * Add extension to given Instance
     */
    public function extensionInstall(Application_Model_Queue $queueElement){
        
        $this->db->update('queue', array('status' => 'installing-extension'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'installing-extension'), 'id=' . $queueElement->getInstanceId());
        
        //get instance data
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
        $queueItem = $queueElement;
    
        //get extension data
        $modelExtension = new Application_Model_Extension();
        $extensionData = $modelExtension->find($queueElement->getExtensionId());
        
        $versionModel = new Application_Model_Version();
        $versionModel->find($instanceModel->getVersionId());
        $magentoVersion = $versionModel->getVersion();
        $magentoEdition = $versionModel->getEdition();
        $sampleDataVersion = $versionModel->getSampleDataVersion();
        
        //get user data
        $modelUser = new Application_Model_User();
        $userData = $modelUser->find($queueElement->getUserId());

        //prepare a logger
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $userData->getLogin() . '_' . $instanceModel->getDomain() . '.log');
        $log = new Zend_Log($writer);
        
        
        //first check if we have that files
        if (!file_exists($this->config->extension->directoryPath.'/'.$magentoEdition.'/'.$extensionData->getFileName())){
            $message = 'Extension file for '.$extensionData->getName().' could not be found';
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            return false;
        } else {
            
        }
        
        //untar extension to instance folder
        exec('tar -zxvf '.
            $this->config->extension->directoryPath.'/'.$magentoEdition.'/'.$extensionData->getFileName().
            ' -C '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $userData->getLogin() . '/public_html/'.$instanceModel->getDomain()
        ,$output);
        //output contains unpacked files list, so it should never be empty if unpacking suceed
        if (count($output)==0){
            $message = 'There was an error while installing extension '.$extensionData->getName();
            $this->db->update('queue', array('status' => 'error'), 'id=' . $queueElement->getId());
            $this->db->update('instance', array('status' => 'error'), 'id=' . $queueElement->getInstanceId());
            $this->db->update('instance', array('error_message' => $message), 'id=' . $queueElement->getInstanceId());
            return false;
        }
        $log->log(var_export($output,true),LOG_DEBUG);
        unset($output);
        
        //clear instance cache
        exec('sudo rm -R '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $userData->getLogin() . '/public_html/'.$instanceModel->getDomain().'/var/cache/*');
        
        //set extension as installed
        $this->db->update('queue', array('status' => 'ready'), 'id=' . $queueElement->getId());
        $this->db->update('instance', array('status' => 'ready'), 'id=' . $queueElement->getInstanceId());
        
    }
    
    /**
     * FFU
     * Remove extension files from instance
     */
    public function extensionRemove(Application_Model_Queue $queueElement){
     
        
        $userModel = new Application_Model_User();
        $instanceOwner = $userModel->find($queueElement->getUserId());
        
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
        
        //remove entry from instance_extension
        $this->db->delete('instance_extension',array(
            'extension_id='.$queueElement->getExtensionId(),
            'instance_id='.$queueElement->getInstanceId()
                ));
        
        //remove extension config from etc/modules
        exec('sudo rm -R '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $instanceOwner->getLogin() . '/public_html/'.$instanceModel->getDomain().'/var/cache/*');
        
        //clear instance cache
        exec('sudo rm -R '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $instanceOwner->getLogin() . '/public_html/'.$instanceModel->getDomain().'/var/cache/*');
    }
    
    /**
     * Replace encoded files with decoded files
     * after client bought an extension
     */
    public function extensionOpenSource(Application_Model_Queue $queueElement){
        
    }
    
    /**
     * Return to state before last extension install
     */
    public function revisionRollback(Application_Model_Queue $queueElement){
        
    }
    
    /**
     * Push changes to git, after you manually changed ftp files
     */
    public function revisionCommit(Application_Model_Queue $queueElement){
        
    }
    
    /**
     * deploy revision X to instance
     */
    public function revisionDeploy(Application_Model_Queue $queueElement){
        
    }
    
    
    /* Runs specific method depending on task type */
    public function process(Application_Model_Queue $queueElement){
               
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methodName = $filter->filter($queueElement->getTask());
        
        $this->$methodName($queueElement);

    }
}