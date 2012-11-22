<?php

class Application_Model_Task_Magento_Download 
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    /* In Bytes */
    protected $_sqlFileLimit = '60000000';
    
    protected $_customHost = '';
    protected $_customSql = '';
    protected $_customRemotePath = ''; 
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
    }
    
    public function process() {
          
        $this->_updateStatus('installing');
        $this->_createSystemAccount();
        
        $this->_updateStatus('installing-magento');

        $startCwd = getcwd();
              
        $log = $this->_getLogger();
        
        $message = 'domain: ' . $this->_domain;
        $log->log($message, LOG_DEBUG);
     
        $storeurl = $this->config->magento->storeUrl . '/instance/' . $this->_domain; //fetch from zend config
        $message = 'store url: ' . $storeurl;
        $log->log($message, LOG_DEBUG);

        chdir($this->_instanceFolder);

        $this->_prepareCustomVars();
                
        //do a sample connection to wget to check if protocol credentials are ok
        $this->_checkProtocolCredentials();

        //connect through wget
        $this->_checkDatabaseDump();
        
        $this->_setupFilesystem();

        chdir($this->_domain);
        
        $this->_updateStatus('installing-files');
        $this->_downloadInstanceFiles(); 
        
        $this->_updateStatus('installing-data');
        $this->_downloadDatabase();
       
        //let's load sql to mysql database
        $this->_importDatabaseDump();

        $this->_updateStatus('installing-magento');
        $this->_importFiles();

        //now lets configure our local xml file
        $this->_updateLocalXml();

        $this->_setupMagentoConnect();
        //remove main fetched folder
        $parts = explode('/',$this->_customRemotePath);
        exec('sudo rm '.$parts[0].' -R', $output);
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
        $set = array('backend_password' => $this->_adminpass);
        $where = array('domain = ?' => $this->_domain);
        $log->log(PHP_EOL . 'Updating queue backend password: ' . $this->db->update('instance', $set, $where), Zend_Log::DEBUG);
        // end
        // create magento connect ftp config and remove settings for free user
        
        // end

        //copy new htaccess over
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/Custom/.htaccess ' . $this->_instanceFolder . '/' . $this->_domain . '/.htaccess');

        //applying patches for xml-rpc issue
        if ($this->_versionObject->getVersion() > '1.3.2.3' AND $this->_versionObject->getVersion() < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Response.php');

        } elseif ($this->_versionObject->getVersion() == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Response.php');

        } elseif ($this->_versionObject->getVersion() > '1.4.9.9' AND $this->_versionObject->getVersion() < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_instanceFolder . '/' . $this->_domain . '/lib/Zend/XmlRpc/Response.php');
        }

        exec('sudo chown -R '.$this->config->magento->userprefix.$this->_dbuser.':'.$this->config->magento->userprefix.$this->_dbuser.' '.$this->_instanceFolder.'/'.$this->_domain, $output);
        $message = var_export($output, true);
        $log->log("\nsudo chown -R ".$this->config->magento->userprefix.$this->_dbuser.':'.$this->config->magento->userprefix.$this->_dbuser.' '.$this->_instanceFolder.'/'.$this->_domain."\n" . $message, LOG_DEBUG);
        unset($output);

        //update core_config_data with new url
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' -e "UPDATE core_config_data SET value = \''.$this->config->magento->storeUrl.'/instance/'.$this->_domain.'/\' WHERE path=\'web/unsecure/base_url\'"');
        exec('mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' -e "UPDATE core_config_data SET value = \''.$this->config->magento->storeUrl.'/instance/'.$this->_domain.'/\' WHERE path=\'web/secure/base_url\'"');

        echo "Finished installing Magento\n";

        //TODO: add mail info about ready installation
        exec('ln -s ' . $this->_instanceFolder . '/' . $this->_domain . ' '.INSTANCE_PATH . $this->_domain);
        $log->log(PHP_EOL . 'ln -s ' . $this->_instanceFolder . '/' . $this->_domain . ' '. INSTANCE_PATH . $this->_domain, Zend_Log::DEBUG);
        
        $this->_updateStatus('ready');
        
        chdir($startCwd);

        /* send email to instance owner start */
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');

        // assign values
        $html->assign('domain', $this->_domain);
        $html->assign('storeUrl', $this->config->magento->storeUrl);
        $html->assign('admin_login', $this->_adminuser);
        $html->assign('admin_password', $this->_adminpass);
        
        // render view
        $bodyText = $html->render('queue-item-ready.phtml');

        // create mail object
        $mail = new Zend_Mail('utf-8');
    
        // configure base stuff
        $mail->addTo($this->_userObject->getEmail());
        $mail->setSubject($this->config->cron->queueItemReady->subject);
        $mail->setFrom($this->config->cron->queueItemReady->from->email, $this->config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
        try {
          $mail->send();
        } catch (Zend_Mail_Transport_Exception $e){
            
	  $log->log('Mail could not be sent', LOG_CRIT, $e->getTraceAsString());
        }
        /* send email to instance owner stop */

        //fetch custom instances fetch
        flock($fp, LOCK_UN); // release the lock  
    }

    protected function _prepareCustomVars() {
        //HOST
        $customHost = $this->_instanceObject->getCustomHost();
        //make sure custom host have slash at the end
        if(substr($customHost,-1)!="/"){
            $customHost .= '/';
        }
        
        //make sure remote path contains prefix:
        if ($this->_instanceObject->getCustomProtocol()=='ftp'){
            if(substr($customHost, 0, 6)!='ftp://'){
                $customHost = 'ftp://'.$customHost;
            }
        }
        $this->_customHost = $customHost;

        //PATH
        $customRemotePath = $this->_instanceObject->getCustomRemotePath();
        //make sure remote path containts slash at the end
        if(substr($customRemotePath,-1)!="/"){
            $customRemotePath .= '/';
        }

        //make sure remote path does not contain slash at the beginning       
        if(substr($customRemotePath,0,1)=="/"){
            $customRemotePath = substr($customRemotePath,1);
        }
        $this->_customRemotePath = $customHost;
       
        //FILE
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $this->_instanceObject->getCustomSql();
        if(substr($customSql,0,1)=="/"){
            $customSql = substr($customSql,1);
        }
        
        $this->_customSql = $customSql;

    }

    protected function _checkProtocolCredentials() {
        exec("wget --spider ".$this->_customHost." ".
             "--passive-ftp ".
             "--user='".$this->_instanceObject->getCustomLogin()."' ".
             "--password='".$this->_instanceObject->getCustomPass()."' ".
             "".$this->_customHost." 2>&1 | grep 'Logged in!'",$output);

        $this->_updateStatus('installing-data');
                
        if (!isset($output[0])){
            $message = 'Protocol credentials does not match';
            $this->_updateStatus('error',$message);          
        }
    }

    /* check if database file exist and is not bigger than limit */

    protected function _checkDatabaseDump() {
        $log = $this->_getLogger();
        exec("wget --spider ".$this->_customHost.$this->_customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'",$output);

        $message = var_export($output, true);
        $log->log("wget --spider ".$this->_customHost.$this->_customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'\n" . $message, LOG_DEBUG);

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
            $this->_updateStatus('error', $message);            
            return false;
        }
        unset($output);

        if ($sqlSizeInfo[1] > $this->_sqlFileLimit){
            $message = 'Sql file is too big, aborting';
            //echo $message;
            $this->_updateStatus('error', $message);            
            return false; //jump to next queue element
        }
    }

    /* should check if you can find Mage/App.php there */

    protected function _validateRemotePath() {
        
    }

    protected function _setupFilesystem() {
        $log = $this->_getLogger();
        echo "Preparing directory...\n";
        exec('sudo mkdir ' . $this->_instanceFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
        $log->log($message, LOG_DEBUG);
        unset($output);

        if (!file_exists($this->_instanceFolder . '/' . $this->_domain) || !is_dir($this->_instanceFolder . '/' . $this->_domain)) {
            $message = 'Directory does not exist, aborting';
            $this->_updateStatus('error',$message);
        }

        exec('sudo chmod +x ' . $this->_instanceFolder . '/' . $this->_domain, $output);
        $message = var_export($output, true);
        $log->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);
    }

    protected function _downloadInstanceFiles() {
        $log = $this->_getLogger();
         echo "Copying package to target directory...\n";
        //do a sample connection, and check for index.php, if it works, start fetching
        $command = "wget --spider ".$this->_customHost.$this->_customRemotePath."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'";
        exec($command, $output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);

        $sqlSizeInfo = explode(' ... ',$output[0]);

       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){
            $message = 'Couldn\'t find app/Mage.php file data, will not install queue element';
            $this->_updateStatus('error', $message);
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
             "-I '".$this->_customRemotePath."app,".$this->_customRemotePath."downloader,".$this->_customRemotePath."errors,".$this->_customRemotePath."includes,".$this->_customRemotePath."js,".$this->_customRemotePath."lib,".$this->_customRemotePath."pkginfo,".$this->_customRemotePath."shell,".$this->_customRemotePath."skin' " .
             "--user='".$this->_instanceObject->getCustomLogin()."' ".
             "--password='".$this->_instanceObject->getCustomPass()."' ".
             "".$this->_customHost.$this->_customRemotePath."";
        exec($command, $output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);
    }

    protected function _downloadDatabase() {
        $log = $this->_getLogger();
        
        $command = "wget  ".$this->_customHost.$this->_customSql." ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." ";
        exec($command,$output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);
    }

    protected function _importDatabaseDump() {
        $path_parts = pathinfo($this->_customSql);
        exec('sudo mysql -u' . $this->config->magento->userprefix . $this->_dbuser . ' -p' . $this->_dbpass . ' ' . $this->config->magento->instanceprefix . $this->_dbname . ' < '.$path_parts['basename'].'');
    }
    
    protected function _importFiles(){
        
        $log = $this->_getLogger();
        
        echo "Moving downloaded sources to main folder...\n";
        exec('sudo mv '.$this->_customRemotePath.'* .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo mv ".$this->_customRemotePath."* .\n" . $message, LOG_DEBUG);
        unset($output);
    }

    protected function _updateLocalXml() {
        $connectionString = '<connection>
                    <host><![CDATA[localhost]]></host>
                    <username><![CDATA['.$this->config->magento->userprefix . $this->_dbuser.']]></username>
                    <password><![CDATA['.$this->_dbpass.']]></password>
                    <dbname><![CDATA['.$this->config->magento->instanceprefix . $this->_dbname.']]></dbname>
                    <active>1</active>
                </connection>';

        $localXml = file_get_contents($this->_instanceFolder . '/' . $this->_domain.'/app/etc/local.xml');
        $localXml = preg_replace("#<connection>(.*?)</connection>#is",$connectionString,$localXml);
        file_put_contents($this->_instanceFolder .'/'. $this->_domain.'/app/etc/local.xml',$localXml);
        unset($localXml);
    }

    protected function _cleanupFilesystem() {
        
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
            'magento_root' => $this->_instanceFolder.'/'.$this->_domain,
            'remote_config' => $ftp_user_host.'/public_html/'.$this->_domain
        );
        $free_user = $this->_userObject->getGroup() == 'free-user' ? true : false;
        if($free_user AND !stristr($this->_versionObject->getVersion(), '1.4')) {
            // index.php file
            $index_file = file_get_contents($this->_instanceFolder.'/'.$this->_domain.'/downloader/index.php');
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
                $this->_instanceFolder.'/'.$this->_domain.'/downloader/index.php',
                $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($this->_instanceFolder.'/'.$this->_domain.'/downloader/template/header.phtml');
            file_put_contents(
                $this->_instanceFolder.'/'.$this->_domain.'/downloader/template/header.phtml',
                    preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        
        file_put_contents($this->_instanceFolder.'/'.$this->_domain.'/downloader/connect.cfg', $header.serialize($connect_cfg));
    }

    protected function _updateCoreConfigData() {
        
    }
    
}