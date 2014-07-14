<?php

class Application_Model_Transport_Ftp extends Application_Model_Transport {
          
    protected $_customHost = '';
    protected $_customPort = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = '';

    protected $_wget;

    public function setup(Application_Model_Store &$store, $logger = NULL, $config = NULL, $cli = NULL){
        $this->_storeObject = $store;
        
        parent::setup($store, $logger, $config, $cli);
        $this->_prepareCustomVars($store);

        $this->_wget = $this->cli('wget');
        $this->_wget->ftpConnect(
            $this->_storeObject->getCustomLogin(),
            $this->_storeObject->getCustomPass(),
            $this->_customHost,
            $this->_customPort
        );
        $this->_wget->addLimits($this->_wgetTimeout, $this->_wgetTries);
    }
    
    public function checkProtocolCredentials(){
        $command = $this->_wget->cloneObject()->checkOnly(true);
        $output = $command->call()->getLastOutput();

        $loggedIn = false;

        foreach ($output as $line){
            if (strpos($line, 'Logged in!')) {
                $loggedIn = true;
                break;
            }
        }

        $message = var_export($output, true);
        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
        $this->logger->log($command."\n" . $message, LOG_DEBUG);

        if (!$loggedIn){
            throw new Application_Model_Transport_Exception('Couldn\'t log in with given ftp credentials. Please change them to try again.');
        }
        return true;
    }
    
    protected function _prepareCustomVars(Application_Model_Store $storeObject){
        //HOST - remove ending slash because we need it after port number
        $customHost = $this->_storeObject->getCustomHost();
        $customHost = rtrim($customHost, '/');
        
        //make sure remote path contains prefix:
        if(substr($customHost, 0, 6)!='ftp://'){
            $customHost = 'ftp://'.$customHost;
        }
        $this->_customHost = $customHost;
        
        ///PORT
        $customPort = (int) $this->_storeObject->getCustomPort();
        if ($customPort == 0){
            $customPort = 21;
        }
        
        $this->_customPort = $customPort;
        
        
        //PATH
        $customRemotePath = $this->_storeObject->getCustomRemotePath();
        //make sure remote path containts slash at the end
        if(substr($customRemotePath,-1)!="/"){
            $customRemotePath .= '/';
        }
        
        //make sure remote path containts slash at the beginning
        if (substr($customRemotePath, 0, 1)!='/'){
            $customRemotePath = '/'.$customRemotePath;
        }

        $this->_customRemotePath = $customRemotePath;

        //SQL
         //make sure sql file path does contain slash at the beginning       
        $customSql = $this->_storeObject->getCustomSql();
        if (substr($customSql, 0, 1)!='/'){
            $customSql = '/'.$customSql;
        }
        $this->_customSql = $customSql;

        //FILE
         //make sure sql file path does contain slash at the beginning       
        $customFile = $this->_storeObject->getCustomFile();
        $customFile = '/'.ltrim($customFile,'/');

        $this->_customFile = $customFile;

        return true;
    }
    
    public function downloadFilesystem(){
        
        if ($this->_storeObject->getCustomFile()!=''){
            return $this->_downloadAndUnpack();
        } else {
            return $this->_downloadStoreFiles();
        }

    }
    
    /* todo: make this protected */
    protected function _downloadStoreFiles(){
        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Starting the download of store files ... this can take a while', Zend_Log::INFO);
        }
        //do a sample connection, and check for index.php, if it works, start fetching
        $command = $this->_wget->cloneObject()->setRootPath($this->_customRemotePath.'app/Mage.php')->getFileSize();
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
        $this->logger->log($command."\n" . $message, LOG_DEBUG);

        $sqlSizeInfo = '';
        if(isset($output[0])) {
            $sqlSizeInfo = $output[0];
        }

        //limit is in bytes!
        if(!is_numeric($sqlSizeInfo) || $sqlSizeInfo == 0){
            throw new Application_Model_Transport_Exception('/app/Mage has not been found');
        }
        unset($output);

        $command = $this->_wget->cloneObject();
        $exclude = array(
            $this->_customRemotePath.'media',
            $this->_customRemotePath.'var',
            $this->_customRemotePath.'.htaccess'
        );
        $command->downloadRecursive($this->_customRemotePath, $exclude);
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
        $this->logger->log($command."\n" . $message, LOG_DEBUG);

        unset($output);
        
        /**
         * TODO: validate output
         */
        $this->_moveFiles();
        
        return true;
    }

    public function checkDatabaseDump(){
        $command = $this->_wget->cloneObject()->setRootPath($this->_customSql)->getFileSize();
        $output = $command->call()->getLastOutput();

        $sqlSizeInfo = '';
        if(isset($output[0])) {
            $sqlSizeInfo = $output[0];
        }

        //limit is in bytes!
        if(!is_numeric($sqlSizeInfo) || $sqlSizeInfo == 0){
            $this->_errorMessage = 'Couldn\'t find sql data file.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if((int) $sqlSizeInfo > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        return true;
    }
    
    protected function _checkStoreDump(){
        $command = $this->_wget->cloneObject()->setRootPath($this->_customFile)->getFileSize();
        $output = $command->call()->getLastOutput();
        $packageSizeInfo = '';
        if(isset($output[0])) {
            $packageSizeInfo = $output[0];
        }

        //limit is in bytes!
        if(!is_numeric($packageSizeInfo) || $packageSizeInfo == 0){
            $this->_errorMessage = 'Couldn\'t find store package file.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if($packageSizeInfo > $this->_storeFileLimit){
            $this->_errorMessage = 'Store file is too big.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        return true;
    }
    
    public function downloadDatabase(){
        if ($this->logger instanceof Zend_Log) {
            $this->logger->log('Starting the download of database file', Zend_Log::INFO);
        }
        $this->_wget->cloneObject()->downloadFile($this->_customSql)->call();
        /* TODO: validate if local and reomte size are correct */
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
        $this->_checkStoreDump();

        //download file
        $command = $this->_wget->cloneObject()->downloadFile($this->_customFile);
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $command = $this->changePassOnStars(escapeshellarg($this->_storeObject->getCustomPass()), $command->toString());
        $this->logger->log($command."\n" . $message, LOG_DEBUG);
        unset($output);
        
        /*TODO: validate output, that file really existed */
        
        
        //unpack to temp location
        $file = $this->cli('file');
        $file->create('temporarystoredir/', $file::TYPE_DIR)->call();

        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        
        /**
         * Get filename out of path, 
         * becasue we have only downloaded file without filepath 
         */
        $pathinfo  = pathinfo($this->_customFile);
        $output = $this->cli('tar')->unpack(
            $pathinfo['basename'],
            'temporarystoredir/'
        )->call()->getLastOutput();
        foreach($output as $line) {
            if(
                stristr($line, 'not in gzip format')
                || stristr($line, 'This does not look like a tar archive')
                || stristr($line, 'unexpected end of file')
            ) {
                throw new Application_Model_Transport_Exception('Provided archive containing store files is not valid tar.gz file.');
            }
        }
        
        $this->_moveFiles();
        
        return true;
    }
    
    protected function _moveFiles(){
        //locate mage file 
        $output = array();
        $mageroot = '';
        $file = $this->cli('file');
        $output = $file->find(
            'Mage.php',
            $file::TYPE_FILE,
            '',
            true
        )->call()->getLastOutput();

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

        /* move files from unpacked dir into our store location */
        $output = array();
        #$command = 'sudo mv -f '.$mageroot.'/* '.$mageroot.'/.??* .';
        $file->clear()->move($mageroot.'/', '.', true)->call();
        $file->clear()->remove($mageroot)->call();

        /**
        * Remove main fetched folder 
        * Note: since we use absolute paths: /home/main/something
        * we need to use 1st array element noth 0th
        */
        #echo $this->_customRemotePath;
        $parts = explode('/', $this->_customRemotePath);
        if (isset($parts[1]) && trim($parts[1]) != '') {
            $file->clear()->remove($parts[1])->call();
        }
    }
    
    /*TODO: maybe methods validateFileExist and validateFileSize ? */
    
}
