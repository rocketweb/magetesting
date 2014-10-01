<?php

class Application_Model_Task_Magento 
extends Application_Model_Task {
    
    protected $_dbuser = '';
    protected $_dbpass = '';
    protected $_dbhost = '';
    protected $_dbname = '';
    protected $_systempass = '';
    protected $_systemname = '';
    protected $_db_table_prefix = '';

    protected $_taskMysql;

    public function setup(Application_Model_Queue $queueElement){
        
        parent::setup($queueElement);

        if(in_array($queueElement->getTask(),array('MagentoInstall','MagentoDownload'))){
                       
            $this->_dbhost = 'localhost';
            $this->_dbuser = $this->_userObject->getLogin(); //fetch from zend config
     
            $this->_dbpass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $this->_userObject->getLogin()), 0, 10); //fetch from zend config
            
            $this->_adminuser = $this->_userObject->getLogin();
            $this->_adminpass = $this->_generateAdminPass();          

            $this->_adminfname = $this->_userObject->getFirstname();
            $this->_adminlname = $this->_userObject->getLastname();

            $this->_systemname = $this->_userObject->getSystemAccountName();
                        
            $this->_systempass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $this->_userObject->getLogin()), 10, 10);
            $this->_domain = $this->_storeObject->getDomain();
        }
        
        $this->_dbname = $this->_userObject->getLogin() . '_' . $this->_storeObject->getDomain();

        $db = Zend_Db::factory('PDO_MYSQL',
            array(
                'charset' => 'UTF8',
                'host' => 'localhost',
                'username' => $this->config->magento->userprefix . $this->_dbuser,
                'password' => $this->_dbpass,
                'dbname' => $this->config->magento->storeprefix . $this->_dbname
            )
        );
        $this->_taskMysql = new Application_Model_TaskMysql($db, $this->_db_table_prefix);
    }

    protected function _pkillFtp($systemName = ''){
        if($systemName != ''){
            $command = $this->cli('pkill')->pkill($systemName);
            $output = $command->call()->getLastOutput();

            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            unset($output);
        }else{
            $this->logger->log('Trying to pkill the ftp connection but no system_account_name was given!', Zend_Log::NOTICE);
        }
    }

    protected function _encodeEnterprise($type = 'clean')
    {
        $ioncube = Application_Model_Ioncube_Encode_Store::factory($type);

        try {
            $ioncube->setup(
                $this->_storeObject,
                $this->config,
                $this->cli()->getLogger()
            );

            $ioncube->process();
        } catch(Application_Model_Ioncube_Exception $e) {
            $this->logger->log('Encoding enterprise error:' . $e->getMessage(), Zend_Log::CRIT);

            //remove EE folder recursively then
            $this->logger->log('Removing app/code/core/Enterprise directory recursively.', Zend_Log::INFO);
            $file = $this->cli('file');
            $file->remove($this->_storeFolder.'/'.$this->_storeObject->getDomain().'/app/code/core/Enterprise')->call();

            throw new Application_Model_Task_Exception('Encoding enterprise failed.', 0, $e);
        }
    }

    /**
     * Creates system account for user during store installation (in worker.php)
     */
    protected function _createSystemAccount() {
        if ($this->_userObject->getHasSystemAccount() == 0) {
            
            try{ 
                $this->_userObject->setSystemAccountName($this->config->magento->userprefix . $this->_dbuser)->save();
            } catch (PDOException $e) {
                $message = 'Could not update system_account_name for id: '.$this->_userObject->getId();
                $this->logger->log($message, Zend_Log::CRIT);
                $this->logger->log($e->getMessage(), Zend_Log::DEBUG);
                throw new Application_Model_Task_Exception($message);
            }

            /** WARNING!
             * in order for this to work, when you run this (worker.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php worker.php
             *
             */
            $this->logger->log('Creating system user.', Zend_Log::INFO);
            
            $startDir = getcwd();
            
            $workerfolder = APPLICATION_PATH.'/../scripts/worker';
            chdir($workerfolder);

            $output = $this->cli('user')->create(
                $this->config->magento->userprefix . $this->_dbuser,
                $this->_systempass,
                $this->config->magento->usersalt,
                $this->config->magento->systemHomeFolder
            )->call()->getLastOutput();
            
            if (is_array($output)) {
                foreach ($output as $notice) {
                    if (strpos($notice, 'create_user.sh') !== false) {
                        $this->logger->log(sprintf('User %s could not be created.', $this->config->magento->userprefix . $this->_dbuser), Zend_Log::CRIT, $notice);
                        throw new Application_Model_Task_Exception(
                            'There was a problem adding store, please contact with our support team.'
                        );
                    }
                }
            }

            $DbManager = new Application_Model_DbTable_Privilege($this->dbPrivileged, $this->config);
            $DbManager->addFtp($this->_dbuser, $this->_systempass,
                $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_dbuser);

            $message = var_export($output, true);
            $this->logger->log($message, Zend_Log::DEBUG);

            chdir($startDir);

            if ('free-user' != $this->_userObject->getGroup()) {
                /* send email with account details start */
                $user_details = array(
                    'dbuser' => $this->_dbuser,
                    'dbpass' => $this->_dbpass,
                    'systempass' => $this->_systempass,
                    'email' => $this->_userObject->getEmail(),
                );
                
                $planModel = new Application_Model_Plan();
                $planModel->find($this->_userObject->getPlanId());
                
                if ($planModel->getFtpAccess()){
                    $DbManager->enableFtp($this->_dbuser);
                    $this->_sendFtpEmail($user_details);
                }

                // comented out as we don't provide phpmyadmin access for now (wojtek)
                /*if ($planModel->getPhpmyadminAccess()){
                    $this->_sendPhpmyadminEmail($user_details);
                }*/
                /* send email with account details stop */
            }
            
            $this->_userObject->setHasSystemAccount(1)->save();
            
            $this->_createUserTmpDir();
            $this->_createVirtualHost();
            /*
             * quota disabled because rackspace does not support it
             * in the way we want it to
             */
            //$this->_setUserQuota();
        }
    }
    
    ///////////////////////////
    //TODO Functions
    
    /**
     * Sends email with ftp credentials to user account email
     */
    protected function _sendFtpEmail(array $user_details){
        /* send email with account details start */
        $config = $this->config;
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/');
        
        // assign values
        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());
        $html->assign('ftphost', 'ftp://'.$this->_userObject->getLogin().'.'.$serverModel->getDomain());
        $html->assign('ftpuser', $config->magento->userprefix . $user_details['dbuser']);
        $html->assign('ftppass', $user_details['systempass']);
        
        $html->assign('storeUrl', $config->magento->storeUrl);

        // render view
        try{
            $bodyText = $html->render('_emails/ftp-account-credentials.phtml');
        } catch(Zend_View_Exception $e) {
            $this->logger->log('FTP mail could not be rendered.', Zend_Log::CRIT, $e->getTraceAsString());
        }
        

        // create mail object
        $mail = new Zend_Mail('utf-8');
        // configure base stuff
        $mail->addTo($user_details['email']);
        $mail->setSubject($this->config->cron->ftpAccountCreated->subject);
        $mail->setFrom($this->config->cron->ftpAccountCreated->from->email, $this->config->cron->ftpAccountCreated->from->desc);
        $mail->setBodyHtml($bodyText);
        try {
          $mail->send();
        } catch (Zend_Mail_Transport_Exception $e){
          $this->logger->log('FTP mail could not be sent.', Zend_Log::CRIT, $e->getTraceAsString());
        }
        /* send email with account details stop */
    }
    
    /**
     * Sends email with phpmyadmin credentials to user account email
     */
    protected function _sendPhpmyadminEmail(array $user_details){
        $config = $this->config;
        /* send email with account details start */
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/');
        // assign valeues
        $html->assign('dbhost', $config->magento->dbhost);
        $html->assign('dbuser', $config->magento->userprefix . $user_details['dbuser']);
        $html->assign('dbpass', $user_details['dbpass']);

        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());
        
        $html->assign('storeUrl', $config->magento->storeUrl);

        // render view
        try{
            $bodyText = $html->render('_emails/phpmyadmin-credentials.phtml');
        } catch(Zend_View_Exception $e) {
            $this->logger->log('phpMyAdmin mail could not be rendered.', Zend_Log::CRIT, $e->getTraceAsString());
        }

        // create mail object
        $mail = new Zend_Mail('utf-8');
        // configure base stuff
        $mail->addTo($user_details['email']);
        $mail->setSubject($this->config->cron->phpmyadminAccountCreated->subject);
        $mail->setFrom($this->config->cron->phpmyadminAccountCreated->from->email, $this->config->cron->phpmyadminAccountCreated->from->desc);
        $mail->setBodyHtml($bodyText);
        try {
          $mail->send();
        } catch (Zend_Mail_Transport_Exception $e){
          $this->logger->log('phpMyAdmin mail could not be sent.', Zend_Log::CRIT, $e->getTraceAsString());
        }
    }
    
    protected function _prepareDatabase(){
        try {
            $DbManager = new Application_Model_DbTable_Privilege($this->dbPrivileged, $this->config);
            if (!$DbManager->checkIfDatabaseExists($this->_userObject->getLogin() . '_' . $this->_storeObject->getDomain())){
                $DbManager->createDatabase($this->_userObject->getLogin() . '_' . $this->_storeObject->getDomain());
            }

            if (!$DbManager->checkIfUserExists($this->_userObject->getLogin())) {
                $DbManager->createUser($this->_userObject->getLogin());
            }
        } catch (PDOException $e) {
            $message = 'Could not create database for store';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }
    }
    
    protected function _disableStoreCache(){
        /* update cache setting - disable all */
        $this->_taskMysql->disableStoreCache();
    }
    
    protected function _enableLogging(){
        $this->_taskMysql->enableLogging();
    }
    
    protected function _createVirtualHost(){
        $file = $this->cli('file');
        $file->create(
            '/etc/apache2/sites-available/'.$this->_dbuser.'.'.$this->_serverObject->getDomain(),
            $file::TYPE_FILE
        )->call();
        $file->clear()->create(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser,
            $file::TYPE_DIR
        )->call();

        $file->clear()->fileOwner(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser,
            $this->config->magento->userprefix . $this->_dbuser.':'.$this->config->magento->userprefix . $this->_dbuser
        )->call();
        $file->clear()->fileOwner(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini',
            'root:root'
        )->call();
        $file->clear()->fileMode(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini',
            '644'
        )->call();

        $content = "
        <VirtualHost *:80>
            SetEnv TMPDIR /home/".$this->config->magento->userprefix . $this->_dbuser."/tmp/
            ServerAdmin support@magetesting.com
            ServerName ".$this->_dbuser.".".$this->_serverObject->getDomain()."

            ErrorLog /home/".$this->config->magento->userprefix . $this->_dbuser."/error.log
            CustomLog /home/".$this->config->magento->userprefix . $this->_dbuser."/access.log combined

            AssignUserID ".$this->config->magento->userprefix . $this->_dbuser." ".$this->config->magento->userprefix . $this->_dbuser."

            DocumentRoot /home/".$this->config->magento->userprefix . $this->_dbuser."/public_html/
            <Directory /home/".$this->config->magento->userprefix . $this->_dbuser."/public_html/>
                    Options Indexes FollowSymLinks
                    AllowOverride All
                    Order allow,deny
                    allow from all
            </Directory>
            php_admin_value open_basedir /home/".$this->config->magento->userprefix . $this->_dbuser."
            php_admin_value upload_tmp_dir /home/".$this->config->magento->userprefix . $this->_dbuser."/tmp
        </VirtualHost>";
        
        file_put_contents('/etc/apache2/sites-available/'.$this->_dbuser.'.'.$this->_serverObject->getDomain(), $content);

        $this->logger->log('Enabling apache site.', Zend_Log::INFO);

        $command = $this->cli('apache')->enableSite(
            $this->_dbuser.'.'.$this->_serverObject->getDomain()
        );
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $this->logger->log('Restarting apache.', Zend_Log::INFO);

        $command = $this->cli('service')->reload('apache2');
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $redirector = '<?php '.
        PHP_EOL.'header("Location: ' . $this->config->magento->storeUrl . '/user/dashboard");';
        $fileLocation = '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/public_html/index.php';
        file_put_contents($fileLocation, $redirector);
        $file->clear()->fileMode($fileLocation, 'a+x')->call();
        $file->clear()->fileOwner(
            $fileLocation,
            $this->config->magento->userprefix . $this->_dbuser.':'.$this->config->magento->userprefix . $this->_dbuser
        )->call();
    }
    
    protected function _createUserTmpDir(){
        $file = $this->cli('file');
        $file->create(
            '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/tmp',
            $file::TYPE_DIR
        )->call();
        $file->clear()->fileMode(
            '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/tmp',
            '777'
        )->call();
    }

    /* Running this prevents store from reindex requirement in admin */
    protected function _reindexStore($return = false){
        $command = $this->cli()->createQuery(
            'su ?  -c "timeout 10m /usr/bin/php -c /etc/php5/cli/restricted_cli.ini -f ? -- --reindexall"',
            array(
                $this->config->magento->userprefix . $this->_userObject->getLogin(),
                '/home/'.$this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_storeObject->getDomain().'/shell/indexer.php'
            )
        );
        if($return === true) return $command;
        $command->call();
    }
    
    protected function _setUserQuota(){
        //4GB soft limit
        //5GB hard limit
        $this->cli()->createQuery(
            'quotatool -u :user -b -q :softLimit -l :hardLimit /'
        )->bindAssoc(array(
            ':user' => $this->config->magento->userprefix . $this->_dbuser,
            ':softLimit' => '4000M',
            ':hardLimit' => '5000M'
        ))->call();

        //set grace time (0seconds mean instant)
        $this->cli()->createQuery(
            'quotatool -u ? -b -t ? /',
            array(
                $this->config->magento->userprefix . $this->_dbuser,
                '0 seconds'
            )
        )->call();
    }
    
    /**
     * The purpose of this method is to replace calls to sys_get_temp dir()
     * with calls to getenv('TMPDIR')
     * Each user virtualhost was equipped with 
     * SetEnv TMPDIR /home/$sysuser/tmp/
     * to handle this correctly
     */
    protected function _updateConnectFiles(){
       
        if ($this->_versionObject->getVersion() > '1.4.2.0'){
            $files_to_update = array(
                'downloader/Maged/Model/Config/Abstract.php',
                'downloader/Maged/Model/Connect.php',
                'downloader/Maged/Controller.php',
                'downloader/lib/Mage/Connect/Packager.php',
                'downloader/lib/Mage/Connect/Command/Registry.php',
                'downloader/lib/Mage/Connect/Config.php',
                'downloader/lib/Mage/Connect/Loader/Ftp.php'
            );

            foreach ($files_to_update as $file){
                $filePath = $this->_storeFolder . '/' . $this->_domain.'/'.$file;
                if(file_exists($filePath)){
                    $fileContents = file_get_contents($filePath);
                    $fileContents = str_replace("sys_get_temp_dir()", "getenv('TMPDIR')", $fileContents);
                    file_put_contents($filePath, $fileContents);
                }else{
                    $this->logger->log('_updateConnectFiles(): File doesn\'t exists: '.$file.' (store_id='.$this->_storeObject->getId().')', Zend_Log::ALERT);
                }
            }
            
            //fix cokie path and followlocation for our hosts, we dont need it here and it causes warnings
            $file = $this->_storeFolder . '/' . $this->_domain.'/downloader/lib/Mage/HTTP/Client/Curl.php';
            $fileContents = file_get_contents($file);
            $fileContents = str_replace("const COOKIE_FILE = 'var/cookie';", "const COOKIE_FILE = '".$this->_storeFolder . "/" . $this->_domain."/var/cookie';", $fileContents);
            $fileContents = str_replace('$this->curlOption(CURLOPT_FOLLOWLOCATION, 1);', '$this->curlOption(CURLOPT_FOLLOWLOCATION, 0);', $fileContents);
            file_put_contents($file, $fileContents);
            
        } 
    }

    /**
     * Sets Design -> Head -> Demo Notice to 'Yes'
     */
    protected function _activateDemoNotice(){
        $this->_taskMysql->activateDemoNotice();
    }    

    protected function _updateStoreConfigurationEmails(){
        $userEmail = $this->_userObject->getEmail();
        $this->_taskMysql->updateStoreConfigurationEmails($userEmail);
    }

    protected function _disableLicenseChecking() {
        $file = $this->_storeFolder . '/' . $this->_domain . '/app/etc/modules/Enterprise_License.xml';
        if(file_exists($file)) {
            $replaced = preg_replace('/<active>.*<\/active>/is', '<active>false</active>', file_get_contents($file));
            if($replaced) {
                file_put_contents($file, $replaced);
            }
        }
    }
}
