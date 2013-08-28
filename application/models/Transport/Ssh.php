<?php 

class Application_Model_Transport_Ssh
extends Application_Model_Transport {

    protected $_customHost = '';
    protected $_customPort = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 

    private $_ssh;

    public function setup(Application_Model_Store &$store, $logger = NULL,$config = NULL, $cli = NULL){
        $this->_storeObject = $store;

        parent::setup($store, $logger, $config, $cli);
        
        $this->_prepareCustomVars($store);

        $this->_ssh = $this->cli('ssh');
        $this->_ssh->connect(
            $this->_storeObject->getCustomLogin(),
            $this->_storeObject->getCustomPass(),
            trim($this->_customHost,'/'),
            $this->_customPort
        );
    }

    public function checkProtocolCredentials(){
        $output =
            $this
                ->_ssh
                ->cloneObject()
                ->remoteCall('echo "connected" 2>&1')
                ->pipe('grep "connected"')
                ->call()
                ->getLastOutput();
        if(isset($output[0]) && 'connected' === $output[0]) {
            return true;
        }
        return false;
    }

    /* TODO: move to parent and rewrite if necessary ? */
    protected function _prepareCustomVars(Application_Model_Store $storeObject){
        //HOST
        $customHost = $this->_storeObject->getCustomHost();
        //make sure custom host have slash at the end
        if(substr($customHost,-1)!="/"){
            $customHost .= '/';
        }
        
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
        $this->cli()->exec('set -xv');
        /**
         * First we change to root then use ltrim on abolute path to prevent:
         * tar: Removing leading `/' from member names
         */
        $pack = $this->cli('tar')->newQuery('cd /;');
        $pack->pack('-', ltrim($this->_customRemotePath,'/'), false)->isCompressed(true);
        $pack->exclude(array($this->_customRemotePath.'var', $this->_customRemotePath.'media'));

        $unpack = $this->cli('tar')->unpack('-', '.')->isCompressed()->strip($components)->asSuperUser();

        $command = $this->_ssh->cloneObject()->remoteCall($pack)->pipe($unpack);

        $output = $command->call()->getLastOutput();

        if ($this->logger instanceof Zend_Log) {
            $message = var_export($output, true);
            $this->logger->log('Downloading store files.', Zend_Log::INFO);
            $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
            $this->logger->log("\n" . $command . "\n" . $message, Zend_Log::DEBUG);
        }
        /**
         * TODO: validate output
         */

        //locate mage file
        $mageroot = '';
        $file = $this->cli('file');
        $output = $file->find('Mage.php', $file::TYPE_FILE, '', true)->call()->getLastOutput();
        $this->logger->log($file->toString(). "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);

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

        $output = $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->getSize($this->_customSql)
        )->call()->getLastOutput();

        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Checking database file size.', Zend_Log::INFO);
            $this->logger->log("\n" . var_export($output, true) . "\n", Zend_Log::DEBUG);
        }

        $sqlSizeInfo = '';
        if(isset($output[0])) {
            $sqlSizeInfo = $output[0];
        }

        //limit is in bytes!
        if(!is_numeric($sqlSizeInfo)) {
            $this->_errorMessage = 'Couldn\'t find sql data file, will not install queue element';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if((int) $sqlSizeInfo > $this->_sqlFileLimit) {
            $this->_errorMessage = 'Sql file is too big';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        return true;
    }

    public function downloadDatabase(){
        $components = count(explode('/',trim($this->_customSql, '/')))-1;

        $this->cli()->exec('set -xv');
        /**
         * First we change to root then use ltrim on abolute path to prevent:
         * tar: Removing leading `/' from member names
         */
        $pack = $this->cli('tar')->newQuery('cd /;');
        $pack->pack('-', ltrim($this->_customSql,'/'), false)->isCompressed(true);

        $unpack = $this->cli('tar')->unpack('-', '.')->isCompressed()->strip($components)->asSuperUser();

        $command = $this->_ssh->cloneObject()->remoteCall($pack)->pipe($unpack);

        $output = $command->call()->getLastOutput();

        if ($this->logger instanceof Zend_Log) {
            $message = var_export($output, true);
            $this->logger->log('Downloading store database.', Zend_Log::INFO);
            $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
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
        $output = $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->getSize($this->_customFile)
        )->call()->getLastOutput();

        $packageSizeInfo = '';
        if(isset($output[0])) {
            $packageSizeInfo = $output[0];
        }

        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Checking package file size.', Zend_Log::INFO);
            $this->logger->log("\n" . var_export($output, true) . "\n", Zend_Log::DEBUG);
        }

        if(!is_numeric($packageSizeInfo)) {
            $this->_errorMessage = 'Couldn\'t find data file, will not install queue element';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if ((int) $packageSizeInfo > $this->_storeFileLimit) {
            $this->_errorMessage = 'Store file is too big';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        /* Download file*/
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        $readFile = $this->cli()->createQuery('cat ?', $this->_customFile);

        $unpack = $this->cli('tar')->unpack('-', '.')->isCompressed()->strip($components)->asSuperUser();

        $command = $this->_ssh->cloneObject()->remoteCall($readFile)->pipe($unpack);

        $output = $command->call()->getLastOutput();

        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command);
        $this->logger->log($command. "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);

        foreach($output as $line) {
            if(
                stristr($line, 'not in gzip format')
                || stristr($line, 'This does not look like a tar archive')
                || stristr($line, 'unexpected end of file')
            ) {
                throw new Application_Model_Transport_Exception('Provided archive containing store files is not valid tar.gz file.');
            }
        }

        //locate mage file 
        $output = array();
        $file = $this->cli('file');
        $output = $file->find('Mage.php', $file::TYPE_FILE, '', true)->call()->getLastOutput();
        $this->logger->log($file->toString(). "\n" . var_export($output,true) . "\n", Zend_Log::DEBUG);


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
        $this->cli()->createQuery('mv ?/.??* .', $mageroot)->asSuperUser()->call();

        $this->cli()->createQuery('mv ?/* .', $mageroot)->asSuperUser()->call();
        //echo 'post-mageroot';

        return true;
    }

}
