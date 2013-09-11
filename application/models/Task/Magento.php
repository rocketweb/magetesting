<?php

class Application_Model_Task_Magento 
extends Application_Model_Task {
    
    protected $_dbuser = '';
    protected $_dbpass = '';
    protected $_dbhost = '';
    protected $_dbname = '';
    protected $_systempass = '';
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
                    $this->_userObject->enableFtp();
                    $this->_sendFtpEmail($user_details);
                }
                
                if ($planModel->getPhpmyadminAccess()){
                    $this->_sendPhpmyadminEmail($user_details);
                }
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
        )->asSuperUser()->call();
        $file->clear()->create(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser,
            $file::TYPE_DIR
        )->asSuperUser()->call();

        $this->_createFcgiWrapper();

        $this->_preparePhpIni();

        $file->clear()->fileOwner(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser,
            $this->config->magento->userprefix . $this->_dbuser.':'.$this->config->magento->userprefix . $this->_dbuser
        )->asSuperUser()->call();
        $file->clear()->fileOwner(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini',
            'root:root'
        )->asSuperUser()->call();
        $file->clear()->fileMode(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini',
            '644'
        )->asSuperUser()->call();

        $content = "<VirtualHost *:80>
            SetEnv TMPDIR /home/".$this->config->magento->userprefix . $this->_dbuser."/tmp/
            ServerAdmin support@magetesting.com
            ServerName ".$this->_dbuser.".".$this->_serverObject->getDomain()."

            ErrorLog /home/".$this->config->magento->userprefix . $this->_dbuser."/error.log
            CustomLog /home/".$this->config->magento->userprefix . $this->_dbuser."/access.log combined

            Alias /fcgi-bin/ /home/www-data/".$this->config->magento->userprefix . $this->_dbuser."/
            SuexecUserGroup ".$this->config->magento->userprefix . $this->_dbuser." ".$this->config->magento->userprefix . $this->_dbuser."

            DocumentRoot /home/".$this->config->magento->userprefix . $this->_dbuser."/public_html/
            <Directory /home/".$this->config->magento->userprefix . $this->_dbuser."/public_html/>
                    Options Indexes FollowSymLinks
                    AllowOverride All
                    Order allow,deny
                    allow from all
            </Directory>

        </VirtualHost>";
        
        file_put_contents('/etc/apache2/sites-available/'.$this->_dbuser.'.'.$this->_serverObject->getDomain(), $content);

        $this->cli('apache')->enableSite(
            $this->_dbuser.'.'.$this->_serverObject->getDomain()
        )->asSuperUser()->call();
        $this->cli('service')->reload('apache2')->asSuperUser()->call();

        $redirector = '<?php '.
        PHP_EOL.'header("Location: ' . $this->config->magento->storeUrl . '/user/dashboard");';
        $fileLocation = '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/public_html/index.php';
        file_put_contents($fileLocation, $redirector);
        $file->clear()->fileMode($fileLocation, 'a+x')->asSuperUser()->call();
        $file->clear()->fileOwner(
            $fileLocation,
            $this->config->magento->userprefix . $this->_dbuser.':'.$this->config->magento->userprefix . $this->_dbuser
        )->asSuperUser()->call();
    }
    
    protected function _createUserTmpDir(){
        $file = $this->cli('file');
        $file->create(
            '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/tmp',
            $file::TYPE_DIR
        )->asSuperUser()->call();
        $file->clear()->fileMode(
            '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/tmp',
            '777'
        )->asSuperUser()->call();
    }
    
    protected function _createFcgiWrapper(){
        $file = $this->cli('file');
        $file->create(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php5-fcgi',
            $file::TYPE_FILE
        )->asSuperUser()->call();
        $php5fcgi = '#!/bin/sh'.
        PHP_EOL.'exec /usr/bin/php5-cgi -c /home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini \\'.
        PHP_EOL.'-d open_basedir=/home/'.$this->config->magento->userprefix . $this->_dbuser.' \\'.
        PHP_EOL.'$1';
        file_put_contents('/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php5-fcgi', $php5fcgi);
        $file->clear()->fileMode(
            '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php5-fcgi',
            '755'
        )->asSuperUser()->call();
    }
    
    protected function _preparePhpIni(){
        $userPhpIni = '/home/www-data/'.$this->config->magento->userprefix . $this->_dbuser.'/php.ini';

        $this->cli('file')->copy(
            '/etc/php5/apache2/php.ini',
            $userPhpIni
        )->asSuperUser()->call();
        //regex to replace disable_functions
        $functionsToBlock = array('exec','system','shell_exec','passthru');
        $text = file_get_contents($userPhpIni);

        $currentSetting;
        preg_match_all('#disable_functions =(.*)#i',$text,$currentSetting);
        $currentlyDisabled = explode(',',$currentSetting[1][0]);
        $finalDisabled = array_filter(array_merge($currentlyDisabled,$functionsToBlock));

        //overwrite disable_functions option
        $result = preg_replace('#disable_functions =(.*?)([^\r\n]+)#is','disable_functions = '.implode(',',$finalDisabled),$text).PHP_EOL;
        
        //overwrite upload_tmp_dir option to users dir
        $result = preg_replace('#(;)?upload_tmp_dir(.*?)([^\r\n]+)#is','upload_tmp_dir = /home/'.$this->config->magento->userprefix . $this->_dbuser.'/tmp/'.PHP_EOL,$result);
        
        file_put_contents($userPhpIni,$result);  
    }

    /* Running this prevents store from reindex requirement in admin */
    protected function _reindexStore(){
        $this->cli()->createQuery(
            '-u ? -s php ? --reindex all',
            array(
                $this->config->magento->userprefix . $this->_dbuser,
                '/home/'.$this->config->magento->userprefix . $this->_dbuser.'/public_html/'.$this->_storeObject->getDomain().'/shell/indexer.php'
            )
        )->asSuperUser()->call();
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
        ))->asSuperUser()->call();

        //set grace time (0seconds mean instant)
        $this->cli()->createQuery(
            'quotatool -u ? -b -t ? /',
            array(
                $this->config->magento->userprefix . $this->_dbuser,
                '0 seconds'
            )
        )->asSuperUser()->call();
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
                $fileContents = file_get_contents($this->_storeFolder . '/' . $this->_domain.'/'.$file);
                $fileContents = str_replace("sys_get_temp_dir()", "getenv('TMPDIR')", $fileContents);
                file_put_contents($this->_storeFolder . '/' . $this->_domain.'/'.$file, $fileContents);
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
}
