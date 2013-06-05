<?php 

class Application_Model_Transport_Ssh
extends Application_Model_Transport {

    protected $_customHost = '';
    protected $_customPort = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 

    private $_connection;

    public function setup(Application_Model_Store &$store, $logger = NULL,$config = NULL){

        $this->_storeObject = $store;

        parent::setup($store, $logger, $config);
        
        $this->_prepareCustomVars($store);

        // error suppression added here intentionally as this function
        // throws warning if it can't connect using given host and port (wojtek)
        $this->_connection = @ssh2_connect($this->_customHost, $this->_customPort);

        if ($this->_connection === FALSE) {
            throw new Application_Model_Transport_Exception('Can not connect with ssh server.');
        }

        ssh2_auth_password($this->_connection, $store->getCustomLogin(), $store->getCustomPass());
        
        /*TODO: execute this somewhere, closeConnection() function? */
        //fclose($stream);
    }

    public function checkProtocolCredentials(){

        if (!$this->_connection){
            throw new Application_Model_Transport_Exception('Couldn\'t log in with given ssh credentials. Please change them to try again.');
        }
        else return true;
    }

    /* TODO: move to parent and rewrite if necessary ? */
    protected function _prepareCustomVars(Application_Model_Store $storeObject){
        //HOST
        $customHost = $this->_storeObject->getCustomHost();
        $this->_customHost = $customHost;
        
        //PORT
        $customPort = $this->_storeObject->getCustomPort();
        if (trim($customPort)==''){
            $customPort = 22;
        }
        
        $this->_customPort = $customPort;

        //PATH
        $customRemotePath = $this->_storeObject->getCustomRemotePath();
        //make sure remote path containts slash at the end
        $customRemotePath = rtrim($customRemotePath,'/') . '/';

        //make sure remote path does contain slash at the beginning       
        $customRemotePath = '/' . ltrim($customRemotePath,'/');
        
        $this->_customRemotePath = $customRemotePath;

        //SQL
         //make sure sql file path does contain slash at the beginning       
        $customSql = $this->_storeObject->getCustomSql();
        $customSql = '/' . ltrim($customSql,'/');

        $this->_customSql = $customSql;

        //FILE
         //make sure sql file path does contain slash at the beginning       
        $customFile = $this->_storeObject->getCustomFile();
        $customFile = '/' . ltrim($customFile,'/');

        $this->_customFile = $customFile;

        return true;
    }

    public function downloadFilesystem(){

        if ($this->_storeObject->getCustomFile()!=''){          
            return $this->_downloadAndUnpack();
        } else {
            return $this->_downloadInstanceFiles();
        }

    }

    protected function _downloadInstanceFiles(){

        $components = count(explode('/',trim($this->_customRemotePath, '/')));

        /**
         * the switch is used to not ask for /yes/no about adding host to known hosts
         */
        exec('set -xv');
        /**
         * First we change to root then use ltrim on abolute path to prevent:
         * tar: Removing leading `/' from member names
         */
        $command = 'sshpass -p'.escapeshellarg($this->_storeObject->getCustomPass())
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' -p'.$this->_customPort.' "cd /;tar -zcf - '.ltrim($this->_customRemotePath,'/').' --exclude='.$this->_customRemotePath.'var --exclude='.$this->_customRemotePath.'media"'
                .' | sudo tar -xzvf - --strip-components='.$components.' -C .';
        exec($command,$output);

        if ($this->logger instanceof Zend_Log) {
            $message = var_export($output, true);
            $this->logger->log('Downloading store files.', Zend_Log::INFO);
            $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command);
            $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        }
        /**
         * TODO: validate output
         */

        //locate mage file
        $output = array();
        $mageroot = '';
        $command = 'find -L -name Mage.php';
        exec($command,$output);
        $this->logger->log($command. "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);
        
        
        /* no matchees found */
        if ( count($output) == 0 ){
            throw new Application_Model_Transport_Exception('/app/Mage has not been found');
        }
        
        foreach ($output as $line){
            if(substr($line,-13) == '/app/Mage.php'){
                $mageroot = substr($line,0,strpos($line,'/app/Mage.php'));
                break;
            }
        }
        
        /* no /app/Mage.php found */
        if ($mageroot == ''){
            throw new Application_Model_Transport_Exception('/app/Mage has not been found');
        }

        return true;
    }

    public function checkDatabaseDump(){

        if($output = ssh2_exec($this->_connection, 'du -b '.$this->_customSql.'')) {
            stream_set_blocking($output, true);
            $content = stream_get_contents($output);
        }

        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Checking database file size.', Zend_Log::INFO);
            $this->logger->log("\n" . $content . "\n", Zend_Log::DEBUG);
        }

        // Since du should return something like '12345   filename.ext'
        $duParts = explode("\t",$content);
        $sqlSizeInfo = $duParts[0];

       //limit is in bytes!
        if ($duParts[0] == 'du:' && $duParts[1] == 'cannot' && $duParts[1]=='access'){                       
            $this->_errorMessage = 'Couldn\'t find sql data file, will not install queue element';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if ($sqlSizeInfo > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        return true;
    }

    public function downloadDatabase(){
        $components = count(explode('/',trim($this->_customSql, '/')))-1;

        exec('set -xv');
        /**
         * First we change to root then use ltrim on abolute path to prevent:
         * tar: Removing leading `/' from member names
         */
        $command = 'sshpass -p'.escapeshellarg($this->_storeObject->getCustomPass())
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' -p'.$this->_customPort.' "cd /;tar -zcf - '.ltrim($this->_customSql,'/').'"'
                .' | sudo tar -xzvf - --strip-components='.$components.' -C .';
        exec($command,$output);

        if ($this->logger instanceof Zend_Log) {
            $message = var_export($output, true);
            $this->logger->log('Downloading store database.', Zend_Log::INFO);
            $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command);
            $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        }

        /* TODO:validate if file existss */
        unset($output);
        return true; 

    }

    public function getError(){
        return $this->_errorMessage;
    }
    
    public function getCustomSql(){
       return $this->_customSql;
    }

    public function getCustomHost(){
       return $this->_customHost;
    }

    public function getCustomRemotePath(){
       return $this->_customRemotePath;
    }

    protected function _downloadAndUnpack(){
        /*TODO: validate that custom file really exists */
        if($output = ssh2_exec($this->_connection, 'du -b '.$this->_customFile.'')) {
            stream_set_blocking($output, true);
            $content = stream_get_contents($output);
        }

        $duParts = explode(' ',$content);

        if ($duParts[0] == 'du:' && $duParts[1] == 'cannot' && $duParts[2]=='access'){                       
            $this->_errorMessage = 'Couldn\'t find data file, will not install queue element';
            throw new Application_Model_Transport_Exception($this->_errorMessage);     
        }
        

        /*check downloaded package filesize */
        if($output = ssh2_exec($this->_connection, 'du -b '.$this->_customFile.'')) {
            stream_set_blocking($output, true);
            $content = stream_get_contents($output);
        }

        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Checking package file size.', Zend_Log::INFO);
            $this->logger->log("\n" . $content . "\n", Zend_Log::DEBUG);
        }

        // Since du should return something like '12345   filename.ext'
        $duParts = explode("\t",$content);
        $packageSizeInfo = $duParts[0];

       //limit is in bytes!
        if ($duParts[0] == 'du:' && $duParts[1] == 'cannot' && $duParts[1]=='access'){                       
            $this->_errorMessage = 'Couldn\'t find store package file, will not install';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if ($packageSizeInfo > $this->_storeFileLimit){
            $this->_errorMessage = 'Store file is too big';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        /* Download file*/
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        $command = 'sshpass -p'.escapeshellarg($this->_storeObject->getCustomPass())
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' -p'.$this->_customPort.' "cat '.$this->_customFile.'"'
                .' | sudo tar -xzvf - -C .';
        exec($command,$output);
        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command);
        $this->logger->log($command. "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);

        foreach($output as $line) {
            if(
                stristr($line, 'not in gzip format')
                || stristr($line, 'This does not look like a tar archive')
            ) {
                throw new Application_Model_Transport_Exception('Provided archive containing store files is not valid tar.gz file.');
            }
        }

        //locate mage file 
        $output = array();
        $mageroot = '';
        $command = 'find -L -name Mage.php';
        exec($command,$output);      
        $this->logger->log($command. "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);


        /* no matchees found */
        if ( count($output) == 0 ){
            throw new Application_Model_Transport_Exception('app/Mage has not been found');
        }

        foreach ($output as $line){
          if(substr($line,-13) == '/app/Mage.php'){
            $mageroot = substr($line,0,strpos($line,'/app/Mage.php'));
            break;
          }
        }

        /* no /app/Mage.php found */
        if ($mageroot == ''){
            throw new Application_Model_Transport_Exception('/app/Mage has not been found');
        }

        /* move files from unpacked dir into our instance location */
        //echo 'mageroot:'.$mageroot;
        $output = array();
        $command = 'sudo mv '.$mageroot.'/.??* .';
        exec($command,$output);
        unset($output);
        
        $output = array();
        $command = 'sudo mv '.$mageroot.'/* .';
        exec($command,$output);
        unset($output);
        //echo 'post-mageroot';

        return true;
    }

}
