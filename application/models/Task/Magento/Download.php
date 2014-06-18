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

        $DbManager = new Application_Model_DbTable_Privilege($this->dbPrivileged, $this->config);
        $DbManager->disableFtp($this->_dbuser);

        $this->_updateStoreStatus('downloading-magento');
        $this->_prepareDatabase();
        $this->_createSystemAccount();

        chdir($this->_storeFolder);
        $this->_setupFilesystem();
        chdir($this->_domain);

        /* Instantiate Transport Model */
        try {
            $transportModel = new Application_Model_Transport();
            $transportModel = $transportModel->factory($this->_storeObject, $this->logger, $this->config, $this->_cli);
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

        $this->_fixOwnership();
        $this->_updateMagentoVersion();
        
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
        
	$this->_prepareDatabaseDump();
        //let's load sql to mysql database
        $this->_importDatabaseDump();

        $this->_detectTablePrefix();
        //$this->_importFiles();

        //now lets configure our local xml file
        $this->_updateLocalXml();

        $this->_disableRWMageTestingExtension();

        $this->_setupMagentoConnect();
        
        $this->_cleanupFilesystem();

        $this->_disableLicenseChecking();

        // update backend admin password
        $this->_storeObject->setBackendPassword($this->_adminpass)->save();
        
        $this->logger->log('Changing store backend password.', Zend_Log::INFO);
        $this->logger->log('Store backend password changed to : ' . $this->_adminpass, Zend_Log::DEBUG);

        //copy new htaccess over
        $this->_fileKit->clear()->copy(
            APPLICATION_PATH . '/../data/pkg/Custom/.htaccess',
            $this->_storeFolder . '/' . $this->_domain . '/.htaccess'
        )->call();

        //applying patches for xml-rpc issue
        $this->_applyXmlRpcPatch();

        $this->logger->log('Changed owner of store directory tree.', Zend_Log::INFO);
        $command = $this->_fileKit->clear()->fileOwner(
            $this->_storeFolder . '/' . $this->_domain,
            $this->config->magento->userprefix . $this->_dbuser . ':' . $this->config->magento->userprefix . $this->_dbuser
        );
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $this->_updateCoreConfigData();
		$this->_updateStoreConfigurationEmails();
        $this->_createAdminUser();

        $this->_importAdminFrontname();
        
        $this->_updateDemoNotice();
        $this->_activateDemoNotice();

        $this->_cleanLogTables();
        
        chdir($startCwd);

        /* update revision count */
        $this->db->update('store', array('revision_count' => '0'), 'id=' . $this->_storeObject->getId());
        $this->_storeObject->setRevisionCount(0);

        if('ee' === strtolower($this->_storeObject->getEdition())) {
            $this->_encodeEnterprise('custom');
        }

        $DbManager->enableFtp($this->_dbuser);
    }

        /* move to transport class */
    
    protected function _setupFilesystem() {

        $this->logger->log('Preparing store directory.', Zend_Log::INFO);
        $file = $this->_fileKit->clear();
        if (!file_exists($this->_storeFolder . '/' . $this->_domain)) {
            $output = $file->create(
                $this->_storeFolder . '/' . $this->_domain,
                    $file::TYPE_DIR
            )->call()->getLastOutput();
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
        $output = $file->clear()->fileMode(
            $this->_storeFolder . '/' . $this->_domain, '+x'
        )->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);
        unset($output);
    }

    protected function _importDatabaseDump() {
        $this->logger->log('Importing custom db dump.', Zend_Log::INFO);
        $path_parts = pathinfo($this->_customSql);
        $command = $this->cli('mysql')->append('cat ?', $path_parts['basename']);
        $command->removeDefiners()->pipe(
            $command->newQuery()->connect(
                $this->config->magento->userprefix . $this->_dbuser,
                $this->_dbpass,
                $this->config->magento->storeprefix . $this->_dbname
            )
        );
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        if($output) {
            $error = 'We couldn\'t import your database correctly. Check your sql dump file.';
            $sql_error = '';
            foreach($output as $line) {
                if(preg_match('/^(ERROR .* at line \d+\: .*)$/is', $line, $match)) {
                    $sql_error = $match[1];
                }
                unset($match);
            }
            if($sql_error) {
                $error .= "\n(".$sql_error.')';
            }
            $this->logger->log($error, Zend_Log::ERR);
            throw new Application_Model_Task_Exception($error);
        }
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
            $this->logger->log($message, Zend_Log::ERR);
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

        // revert cache and session type of storage to file type
        $localXml = preg_replace(
            '/\<(cache|session)\>(.*)\<type\>(.*)\<\/type\>(.*)\<\/\1\>/is',
            '<$1>$2<type></type>$4</$1>',
            $localXml
        );

        file_put_contents($this->_storeFolder .'/'. $this->_domain.'/app/etc/local.xml',$localXml);
        unset($localXml);

    }

    protected function _disableRWMageTestingExtension() {
        $file = $this->_storeFolder . '/' . $this->_domain . '/app/etc/modules/RocketWeb_MageTesting.xml';
        if(file_exists($file)) {
            $replaced = preg_replace('/<active>.*<\/active>/is', '<active>false</active>', file_get_contents($file));
            if($replaced) {
                file_put_contents($file, $replaced);
            }
        }
    }

    protected function _cleanupFilesystem() {
        
        $this->logger->log('Setting store directory permissions.', Zend_Log::INFO);

        $file = $this->_fileKit;
        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/var/')) {
            $file->clear()->create('var', $file::TYPE_DIR)->call();
            $file->clear()->create(
                $this->_storeFolder . '/' . $this->_domain . '/var/.htaccess',
                $file::TYPE_FILE
            )->call();
            //create htaccess 
            $lines = array('Order deny,allow',
                PHP_EOL.'Deny from all');
            foreach ($lines as $line){
                $file->newQuery(
                    'echo ? >> ?',
                    array(
                        $line,
                        $this->_storeFolder . '/' . $this->_domain . '/var/.htaccess'
                    )
                )->call();
            }
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/downloader/')) {
            $file->clear()->create('downloader', $file::TYPE_DIR)->call();
        }

        if (!file_exists($this->_storeFolder . '/' . $this->_domain . '/media/')) {
            $file->clear()->create('media', $file::TYPE_DIR)->call();
            $file->clear()->create(
                $this->_storeFolder . '/' . $this->_domain . '/media/.htaccess',
                $file::TYPE_FILE
            )->call();

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
                $file->newQuery(
                    'echo ? >> ?',
                    array(
                        $line,
                        $this->_storeFolder . '/' . $this->_domain . '/var/.htaccess'
                    )
                )->call();
            }
            
            /**
             * This line was here to prevent:
             * 500 OOPS: vsftpd: refusing to run with writable root inside chroot ()
             * when vsftpd is set to use chroot list
             * 
             * We don't vsftp now, but will leave that here for now. (wojtek)
             */
            $file->clear()->fileMode($this->_storeFolder, 'a-w')->call();
        }

        $this->logger->log('Setting store directory permissions.', Zend_Log::INFO);
        $command = $file->clear()->fileMode(':files', '777', false)
            ->bindAssoc("':files'", 'app/etc downloader', false);
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        $command = $file->clear()->fileMode(':files', '777')
            ->bindAssoc("':files'", 'var media', false);
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);

        /* remove git files */
        if (file_exists($this->_storeFolder . '/' . $this->_domain . '/.git/')) {
            $file->clear()->remove('.git/')->call();
        }

        if (file_exists($this->_storeFolder . '/' . $this->_domain . '/.gitignore')) {
            $file->clear()->remove('.gitignore')->call();
        }

        /* remove svn files/folders */
        $file->clear()->find('.svn', $file::TYPE_DIR, '.')->pipe(
            $file->newQuery('xargs')->remove()->force()
        )->call();
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
        $this->_updateConnectFiles();
    }

    protected function _updateCoreConfigData() {
        
        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());

        $this->_taskMysql->updateCoreConfig(
            'http://'.$this->_userObject->getLogin().'.'.$serverModel->getDomain().'/'.$this->_storeObject->getDomain().'/',
            $this->_userObject->getEmail()
        );

        /* clear cache to apply new cache settings  */
        $this->_clearStoreCache();
        
        $this->_enableLogging();
    }
    
    protected function _createAdminUser(){
        $this->_taskMysql->createAdminUser(
            $this->_userObject->getFirstName(),
            $this->_userObject->getLastName(),
            $this->_userObject->getEmail(),
            $this->_userObject->getLogin(),
            $this->getHash($this->_adminpass,2)
        );
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
        
        $this->_storeObject->setBackendName($frontname)->save();
    }

    protected function _cleanLogTables()
    {
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

        foreach ($tablesToClean as $table) {
            try {
                $this->_taskMysql->truncate($table);
                $this->logger->log(sprintf('Table %s truncated successfully.', $table), Zend_Log::DEBUG);
            } catch (Exception $e) {
                $this->logger->log(sprintf('Table %s not truncated.', $table), Zend_Log::DEBUG);
            }
        }
    }

    /**
     * If database dump is a gz
     */
    protected function _prepareDatabaseDump() 
    {

        $output = array();
        $sqlfound = false;
        $unpacked = 0;

        /* check for gz */
        $path_parts = pathinfo($this->_customSql);
        $sqlname = $path_parts['basename'];

        $this->logger->log($sqlname, Zend_Log::DEBUG);

        $not_gzipped = $this->cli('gzip')->test($sqlname)->call()->getLastStatus();
        if ((int)$not_gzipped
        ) {
            $sqlfound = true;
            $unpacked = 1;
        } else {
            /* file is tar.gz or gz */
            /* note: somehow, tar doesn't put anything in $output variable */
            $command = $this->cli('tar')->test($sqlname);
            $result = $command->call();
            $this->logger->log($command, Zend_Log::DEBUG);
            $this->logger->log(var_export($result->getLastOutput(),true), Zend_Log::DEBUG);
            $this->logger->log($result->getLastStatus(), Zend_Log::DEBUG);

            if ((int)$result->getLastStatus()) {
                /* is gz */

                $this->logger->log($sqlname . ' is gz', Zend_Log::DEBUG);

                /**
                 * get filename from output - gz only packs one filename 
                 * this needs to be done BEFORE unpacking otherise we lose file
                 */
                $command = $this->cli('gzip')->getPackedFilename($sqlname);
                $result = $command->call();
                $output = $result->getLastOutput();

                $this->logger->log($command, Zend_Log::DEBUG);
                $this->logger->log(var_export($output,true), Zend_Log::DEBUG);

                if(!(int)$result->getLastStatus() && $output) {
                    $this->_customSql = $output[0];
                    $sqlfound = true;
                }

                /* is gz */
                $command = $this->cli('gzip')->unpack($sqlname);
                $output = $command->call()->getLastOutput();

                $this->logger->log($command, Zend_Log::DEBUG);
                $this->logger->log(var_export($output,true), Zend_Log::DEBUG);
                $unpacked = 1;
            } else {
                /* is tar.gz */
                $this->logger->log($sqlname . ' is tar', Zend_Log::DEBUG);

                $command = $this->cli('tar')->unpack($sqlname);
                $output = $command->call()->getLastOutput();

                $this->logger->log($command, Zend_Log::DEBUG);
                $this->logger->log(var_export($output,true), Zend_Log::DEBUG);

                $unpacked = 1;

                /**
                 * Sample output:
                 * array (size=2)
                  0 => string 'somedir/' (length=8)
                  1 => string 'somedir/somefile.sql' (length=20)
                 */
                foreach ($output as $path) {
                    $output2 = array();
                    if (is_file($path)) {
                        $command = $this->cli()->createQuery(
                            'grep -lir ? ?',
                            array('CREATE TABLE `'.$this->_db_table_prefix . 'admin_role`', $path)
                        );
                        $result = $command->call()->getLastOutput();
                        $this->logger->log($command, Zend_Log::DEBUG);
                        $this->logger->log(var_export($result,true), Zend_Log::DEBUG);

                        if (!empty($result)) {
                            $sqlfound = true;
                            $this->_customSql = $result[0];
                        }
                    }
                }
            }

            if ($sqlfound === true && $unpacked == 1) {
                return true;
            } else {
                $message = 'sql file has not been found in given package';
                $this->logger->log($message, Zend_Log::ERR);
                throw new Application_Model_Task_Exception($message);
            }
        }
    }

    protected function _updateDemoNotice(){
        $this->_taskMysql->updateDemoNotice();
    }

    protected function _detectTablePrefix(){
        $output = array();
        $path_parts = pathinfo($this->_customSql);
        $output = $this->cli()
            ->createQuery('grep -i -e \'[a-z0-9$_]*core_config_data\' ? -o', $path_parts['basename'])
            ->pipe('head -n 1')
            ->pipe('sed s/core_config_data//')
            ->call()
            ->getLastOutput();
        $this->_db_table_prefix = '';
        if(isset($output[0])) {
            $this->_db_table_prefix = $output[0];
            $this->_taskMysql->setTablePrefix($this->_db_table_prefix);
        }
    }

    protected function _fixOwnership(){
        $output = array();
        $user  = $this->config->magento->userprefix . $this->_dbuser;
        $command = $this->_fileKit->clear()->fileOwner(
            $this->_storeFolder.'/'.$this->_storeObject->getDomain().'/',
            $user.':'.$user
        );
        $output = $command->call()->getLastOutput();

        $this->logger->log($command, Zend_Log::DEBUG);
        $this->logger->log(var_export($output, true), Zend_Log::DEBUG);
    }
    
    protected function _updateMagentoVersion()
    {
        $this->logger->log('Checking real Magento version.', Zend_Log::INFO);

        $matches=array();
        $major=array();
        $minor=array();
        $revision=array();
        $patch=array();
        
        $mageFile = $this->_storeFolder.'/'.$this->_storeObject->getDomain().'/app/Mage.php';
        
        $text = file_get_contents($mageFile);

        preg_match('#function getVersionInfo\(\)(.*?)}#is',$text,$matches);

        preg_match("#'major'(.*?)=>(.*?)'([0-9]+)',#is",$matches[0],$major);
        preg_match("#'minor'(.*?)=>(.*?)'([0-9]+)',#is",$matches[0],$minor);
        preg_match("#'revision'(.*?)=>(.*?)'([0-9]+)',#is",$matches[0],$revision);
        preg_match("#'patch'(.*?)=>(.*?)'([0-9]+)',#is",$matches[0],$patch);
       
        $version = $major[3].'.'.$minor[3].'.'.$revision[3].'.'.$patch[3];

        $edition =
            (file_exists($this->_storeFolder.'/'.$this->_storeObject->getDomain().'/app/code/core/Enterprise/')) 
                ? 'EE' : 'CE';

        $versionModel = new Application_Model_Version();
        $versionModel->findByVersionString($version, $edition);

        if (!$versionModel->getId()) {
            $error = sprintf('Magento %s %s is not supported.', $edition, $version);
            $this->logger->log($error, Zend_Log::ERR);
            throw new Application_Model_Task_Exception($error);
        }

        $this->logger->log(sprintf('Magento version successfully found: %s %s changed to %s %s',
            $this->_storeObject->getEdition(),
            $this->_versionObject->getVersion(),
            $edition, $version
        ), Zend_Log::INFO);

        $this->_versionObject = $versionModel;

        $this->_storeObject->setEdition($edition);
        $this->_storeObject->setVersionId($versionModel->getId());
        $this->_storeObject->save();
    }
}
