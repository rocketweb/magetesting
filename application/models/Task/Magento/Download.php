<?php

class Application_Model_Task_Magento_Download 
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    protected $_customHost = '';
    protected $_customSql = '';
    protected $_customRemotePath = ''; 
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        $startCwd = getcwd();

        $this->_updateStoreStatus('downloading-magento');
        $this->_prepareDatabase();
        $this->_createSystemAccount();

        chdir($this->_storeFolder);
        $this->_setupFilesystem();
        chdir($this->_domain);

        /* Instantiate Transport Model */
        try {
            $transportModel = new Application_Model_Transport();
            $transportModel = $transportModel->factory($this->_storeObject, $this->logger, $this->config);
        } catch (Application_Model_Transport_Exception $e) {
            $this->logger->log($e->getMessage(),Zend_Log::ERR);
            throw new Application_Model_Task_Exception($e->getMessage());
        }

        if (!$transportModel){
            $message = 'Protocol "' . $this->_storeObject->getCustomProtocol() . '" is not supported.';
            $this->logger->log($message, Zend_Log::EMERG);
            throw Application_Model_Task_Exception($message);
        }
        
        //do a sample connection to check if protocol credentials are ok
        try {
            $transportModel->checkProtocolCredentials();
        } catch (Application_Model_Transport_Exception $e) {
            $this->logger->log($e->getMessage(),Zend_Log::ERR);
            throw new Application_Model_Task_Exception($e->getMessage());
        }
        
        
        try {
            $transportModel->checkDatabaseDump();
        } catch (Application_Model_Transport_Exception $e) {
            $this->logger->log($e->getMessage(),Zend_Log::ERR);
            throw new Application_Model_Task_Exception($e->getMessage());
        }
        
        try {
            $transportModel->downloadFilesystem();
        } catch (Application_Model_Transport_Exception $e) {
            $this->logger->log($e->getMessage(),Zend_Log::ERR);
            throw new Application_Model_Task_Exception($e->getMessage());
        }
        
        try {
            $transportModel->downloadDatabase();
        } catch (Application_Model_Transport_Exception $e) {
            $this->logger->log($e->getMessage(),Zend_Log::ERR);
            throw new Application_Model_Task_Exception($e->getMessage());
        }
        
        //update custom variables using data from transport
        $this->_customHost = $transportModel->getCustomHost();
        $this->_customSql = $transportModel->getCustomSql();
        $this->_customRemotePath = $transportModel->getCustomRemotePath();

        /* end of transport usage */

        //let's load sql to mysql database
        $this->_importDatabaseDump();

        //$this->_importFiles();

        //now lets configure our local xml file
        $this->_updateLocalXml();

        $this->_setupMagentoConnect();
        
        $this->_cleanupFilesystem();

        // update backend admin password
        $this->_storeObject->setBackendPassword($this->_adminpass)->save();
        //$set = array('backend_password' => $this->_adminpass);
        //$where = array('domain = ?' => $this->_domain);
        $this->logger->log('Changing store backend password.', Zend_Log::INFO);
        $this->logger->log('Store backend password changed to : ' . $this->_adminpass, Zend_Log::DEBUG);

        //copy new htaccess over
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/Custom/.htaccess ' . $this->_storeFolder . '/' . $this->_domain . '/.htaccess');

        //applying patches for xml-rpc issue
        $this->_applyXmlRpcPatch();

        $this->logger->log('Changed owner of store directory tree.', Zend_Log::INFO);
        $command = 'sudo chown -R ' . $this->config->magento->userprefix . $this->_dbuser . ':' . $this->config->magento->userprefix . $this->_dbuser . ' ' . $this->_storeFolder . '/' . $this->_domain;
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $this->_updateCoreConfigData();

        $this->_createAdminUser();

        $this->_importAdminFrontname();

        $this->_cleanLogTables();
        
        $store_path = str_replace('/application/../store/', '/store/', STORE_PATH);

        $this->logger->log('Added symbolic link for store directory.', Zend_Log::INFO);
        $command = 'ln -s ' . $this->_storeFolder . '/' . $this->_domain . ' ' . $store_path . $this->_domain;
        exec($command);
        $this->logger->log(PHP_EOL . $command . PHP_EOL, Zend_Log::DEBUG);

        chdir($startCwd);

        /* send email to store owner start */
        $this->_sendStoreReadyEmail();
        /* send email to store owner stop */

        /* update revision count */
        $this->db->update('store', array('revision_count' => '0'), 'id=' . $this->_storeObject->getId());
        $this->_storeObject->setRevisionCount(0);
        
    }

        /* move to transport class */
    
    protected function _setupFilesystem() {

        $this->logger->log('Preparing store directory.', Zend_Log::INFO);
        if (!file_exists($this->_storeFolder . '/' . $this->_domain)) {
            exec('sudo mkdir ' . $this->_storeFolder . '/' . $this->_domain, $output);
            $message = var_export($output, true);
            $this->logger->log($message, Zend_Log::DEBUG);
            unset($output);
        } else {
            $this->logger->log('Store directory already exists, continuing.', Zend_Log::INFO);
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain) || !is_dir($this->_storeFolder . '/' . $this->_domain)) {
            $message = 'Store directory does not exist, aborting.';
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        }

        $this->logger->log('Changing chmod for domain: ' . $this->_domain, Zend_Log::INFO);
        exec('sudo chmod +x ' . $this->_storeFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);
        unset($output);
    }

    protected function _importDatabaseDump() {
        $this->logger->log('Importing custom db dump.', Zend_Log::INFO);
        $path_parts = pathinfo($this->_customSql);
        $command = 'sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' < '.$path_parts['basename'].'';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        unset($output);
    }
    
    /**
     * @deprecated: this should be taken care of within transport class
     * This 
     */
    protected function _importFiles(){
        
        /*$this->logger->log('Moving downloaded sources to main folder.', Zend_Log::INFO);
        $command = 'sudo mv '.ltrim($this->_customRemotePath,'/').'* .';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);*/
    }

    protected function _updateLocalXml() {
        $localXmlPath = $this->_storeFolder . '/' . $this->_domain.'/app/etc/local.xml';

        if (!file_exists($localXmlPath)) {
            $message = 'Store local.xml config file does not exist.';
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        }

        $connectionString = '<connection>
                    <host><![CDATA[localhost]]></host>
                    <username><![CDATA['.$this->config->magento->userprefix . $this->_dbuser.']]></username>
                    <password><![CDATA['.$this->_dbpass.']]></password>
                    <dbname><![CDATA['.$this->config->magento->storeprefix . $this->_dbname.']]></dbname>
                    <active>1</active>
                </connection>';

        $localXml = file_get_contents($localXmlPath);
        $localXml = preg_replace("#<connection>(.*?)</connection>#is",$connectionString,$localXml);
        file_put_contents($this->_storeFolder .'/'. $this->_domain.'/app/etc/local.xml',$localXml);
        unset($localXml);
    }

    protected function _cleanupFilesystem() {
        
        $this->logger->log('Setting store directory permissions.', Zend_Log::INFO);

        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/var/')) {
            exec('sudo mkdir var');
            exec('sudo touch '.$this->_storeFolder . '/' . $this->_domain . '/var/.htaccess');
            //create htaccess 
            $lines = array('Order deny,allow',
                PHP_EOL.'Deny from all');
            foreach ($lines as $line){
                exec('sudo echo \''.$line.'\' >> '.$this->_storeFolder . '/' . $this->_domain . '/var/.htaccess');
            }
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/downloader/')) {
            exec('sudo mkdir downloader');
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/media/')) {
            exec('sudo mkdir media');
            exec('sudo touch '.$this->_storeFolder . '/' . $this->_domain . '/media/.htaccess');
            
            $lines = array('Options All -Indexes',
            PHP_EOL.'<IfModule mod_php5.c>',
            PHP_EOL.'php_flag engine 0',
            PHP_EOL.'</IfModule>',
            PHP_EOL.'AddHandler cgi-script .php .pl .py .jsp .asp .htm .shtml .sh .cgi',
            PHP_EOL.'Options -ExecCGI',
            PHP_EOL.'<IfModule mod_rewrite.c>',
            PHP_EOL.'',
            PHP_EOL.'############################################',
            PHP_EOL.'## enable rewrites',
            PHP_EOL.'',
            PHP_EOL.'    Options +FollowSymLinks',
            PHP_EOL.'    RewriteEngine on',
            PHP_EOL.'',
            PHP_EOL.'############################################',
            PHP_EOL.'## never rewrite for existing files',
            PHP_EOL.'    RewriteCond %{REQUEST_FILENAME} !-f',
            PHP_EOL.'',
            PHP_EOL.'############################################',
            PHP_EOL.'## rewrite everything else to index.php',
            PHP_EOL.'',
            PHP_EOL.'    RewriteRule .* ../get.php [L]',
            PHP_EOL.'</IfModule>');
            
            foreach ($lines as $line){
                exec('sudo echo \''.$line.'\' >> '.$this->_storeFolder . '/' . $this->_domain . '/media/.htaccess');
            }
            
        }
        
        
        //add var/.htaccess if not exist       
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

        //add media if not exist
        $command = 'sudo chmod 777 media -R';
        exec($command, $output);
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        /* remove git files */
        if (file_exists($this->_storeFolder . '/' . $this->_domain . '/.git/')) {
            exec('rm .git/ -R');
        }

        if (file_exists($this->_storeFolder . '/' . $this->_domain . '/.gitignore')) {
            exec('rm .gitignore');
        }

        /* remove svn files/folders */
        exec('rm -rf `find . -type d -name .svn`');
    }

    protected function _setupMagentoConnect() {
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
            'ftp://',
            'ftp://'.$this->config->magento->userprefix.$this->_dbuser.':'.$this->_dbpass.'@',
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
            'magento_root' => $this->_storeFolder.'/'.$this->_domain,
            'remote_config' => $ftp_user_host.'/public_html/'.$this->_domain
        );
        
        $free_user = $this->_userObject->getGroup() == 'free-user' ? true : false;
        if($free_user AND !stristr($this->_versionObject->getVersion(), '1.4')) {
            // index.php file
            $index_file = file_get_contents($this->_storeFolder.'/'.$this->_domain.'/downloader/index.php');
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
                $this->_storeFolder.'/'.$this->_domain.'/downloader/index.php',
                $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($this->_storeFolder.'/'.$this->_domain.'/downloader/template/header.phtml');
            file_put_contents(
                $this->_storeFolder.'/'.$this->_domain.'/downloader/template/header.phtml',
                    preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        
        file_put_contents($this->_storeFolder.'/'.$this->_domain.'/downloader/connect.cfg', $header.serialize($connect_cfg));
    }

    protected function _updateCoreConfigData() {
        
        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());
                
        //update core_config_data with new url
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE \`core_config_data\` SET \`value\` = \''.'http://'.$serverModel->getDomain().'/store/'.$this->_domain.'/\' WHERE \`path\`=\'web/unsecure/base_url\'"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE \`core_config_data\` SET \`value\` = \''.'http://'.$serverModel->getDomain().'/store/'.$this->_domain.'/\' WHERE \`path\`=\'web/secure/base_url\'"');

        //update contact emails
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \''.$this->_userObject->getEmail().'\' WHERE  \`path\` = \'contacts/email/recipient_email\';"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \''.$this->_userObject->getEmail().'\' WHERE  \`path\` = \'catalog/productalert_cron/error_email\';"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \''.$this->_userObject->getEmail().'\' WHERE  \`path\` = \'sitemap/generate/error_email\';"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \''.$this->_userObject->getEmail().'\' WHERE  \`path\` = \'sales_email/order/copy_to\';"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \''.$this->_userObject->getEmail().'\' WHERE  \`path\` = \'sales_email/shipment/copy_to\';"');
        
        /* Disable Google Analytics */
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \'0\' WHERE  \`path\` = \'google/analytics/active\';"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . ' -e "UPDATE  \`core_config_data\` SET  \`value\` =  \'\' WHERE  \`path\` = \'google/analytics/account\';"');
        
        /* clear cache to apply new cache settings  */
        $this->_clearStoreCache();
        
        $this->_disableStoreCache();
        
        $this->_enableLogging();
    }
    
    protected function _createAdminUser(){
               
        /* add user */
        $password = $this->getHash($this->_adminpass,2);
        $command = 'mysql -u' . $this->config->magento->userprefix . $this->_dbuser . 
        ' -p' . $this->_dbpass . 
        ' ' . $this->config->magento->storeprefix . $this->_dbname . 
        ' -e "INSERT INTO admin_user'.
        ' (firstname,lastname,email,username,password,created,is_active) VALUES'.
        ' (\''.$this->_userObject->getFirstName().'\',\''.$this->_userObject->getLastName().'\',\''.$this->_userObject->getEmail().'\',\''.$this->_userObject->getLogin().'\',\''.$password.'\',\''.date("Y-m-d H:i:s").'\',1)'.
        ' ON DUPLICATE KEY UPDATE password = \''.$password.'\', email = \''.$this->_userObject->getEmail().'\' "';
        exec($command, $output);
        unset($output);
        
        /* add role for that user */
        $command = 'mysql -u' . $this->config->magento->userprefix . $this->_dbuser . 
        ' -p' . $this->_dbpass . 
        ' ' . $this->config->magento->storeprefix . $this->_dbname . 
        ' -e "INSERT INTO admin_role'. 
' (parent_id,tree_level,sort_order,role_type,user_id,role_name)'. 
' VALUES'.
' (1,2,0,\'U\',(SELECT user_id FROM admin_user WHERE username=\''.$this->_userObject->getLogin().'\'),\''.$this->_userObject->getFirstName().'\')"';
        exec($command, $output);
        unset($output);
        
        
    }
    
    /* taken from Mage_Core_Helper_Data */
    public function getRandomString($len, $chars=null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
    
    /* taken from Mage_Core_Model_Encryption, just removed ->_helper */
    /**
     * Generate a [salted] hash.
     *
     * $salt can be:
     * false - a random will be generated
     * integer - a random with specified length will be generated
     * string
     *
     * @param string $password
     * @param mixed $salt
     * @return string
     */
    public function getHash($password, $salt = false)
    {
        if (is_integer($salt)) {
            $salt = $this->getRandomString($salt);
        }
        return $salt === false ? $this->hash($password) : $this->hash($salt . $password) . ':' . $salt;
    }

    /**
     * Hash a string
     *
     * @param string $data
     * @return string
     */
    public function hash($data)
    {
        return md5($data);
    }
    
    protected function _importAdminFrontname(){
        
        $localXml = file_get_contents($this->_storeFolder . '/' . $this->_domain.'/app/etc/local.xml');
        preg_match("#<frontName>(.*?)</frontName>#is",$localXml,$matches);
        
        if (isset($matches[1])){
            $frontname = str_replace(array('<![CDATA[',']]>'),'',$matches[1]);
        
            if (trim($frontname)!=''){
                $set = array('backend_name' => $frontname);
            } else {
                 $set = array('backend_name' => 'admin');
            }
        } else {
            $set = array('backend_name' => 'admin');
        }
     
        $where = array('id = ?' => $this->_storeObject->getId());
        $this->db->update('store', $set, $where);
    }
    
    protected function _cleanLogTables(){
        
        $tablesToClean = array(
            'log_customer',
            'log_quote',
            'log_summary',
            'log_summary_type',
            'log_url',
            'log_url_info',
            'log_visitor',
            'log_visitor_info',
            'log_visitor_online',
            'sendfriend_log'
        );
        
        foreach ($tablesToClean as $tableName){
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . 
                ' -p' . $this->_dbpass . ' ' . $this->config->magento->storeprefix . $this->_dbname . 
                ' -e "TRUNCATE TABLE \`'.$tableName.'\`"');
        }
    }
    
}
