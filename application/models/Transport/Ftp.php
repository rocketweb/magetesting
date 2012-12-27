<?php

class Application_Model_Transport_Ftp extends Application_Model_Transport {
          
    protected $_customHost = '';
    protected $_customPort = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 
    
    public function setup(Application_Model_Store &$store, $logger = NULL){
        
        $this->_storeObject = $store;
        
        parent::setup($store, $logger);
        $this->_prepareCustomVars($store);
    }
    
    public function checkProtocolCredentials(){
        exec("wget --spider ".$this->_customHost.":".$this->_customPort." ".
             "--passive-ftp ".
             "--user='".$this->_storeObject->getCustomLogin()."' ".
             "--password='".$this->_storeObject->getCustomPass()."' ".
             "".$this->_customHost.":".$this->_customPort." 2>&1 | grep 'Logged in!'",$output);

        if (!isset($output[0])){
            throw new Application_Model_Transport_Exception('Couldn\'t log in with given ftp credentials');
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
        $customPort = $this->_storeObject->getCustomPort();
        if (trim($customPort)==''){
            $customPort = 21;
        }
        
        //make sure custom port have slash at the end
        //if(substr($customPort,-1)!="/"){
//            $customPort .= '/';
//        }
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

        //make sure remote path does not contain slash at the beginning
        //$customRemotePath = ltrim($customRemotePath, '/');
        $this->_customRemotePath = $customRemotePath;

        //SQL
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $this->_storeObject->getCustomSql();
        //make sure remote path containts slash at the beginning
        if (substr($customSql, 0, 1)!='/'){
            $customSql = '/'.$customSql;
        }
        $this->_customSql = $customSql;

        //FILE
         //make sure sql file path does not contain slash at the beginning       
        $customFile = $this->_storeObject->getCustomFile();
        $customFile = ltrim($customFile,'/');

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
        //do a sample connection, and check for index.php, if it works, start fetching
        $command = "wget --spider ".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_storeObject->getCustomLogin()."' ".
            "--password='".$this->_storeObject->getCustomPass()."' ".
            "".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath." | grep 'SIZE'";
        exec($command, $output);
        //$message = var_export($output, true);
        //$log->log($command."\n" . $message, LOG_DEBUG);

        $sqlSizeInfo = explode(' ... ',$output[0]);

       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){
            throw new Application_Model_Transport_Exception('/app/Mage has not been found');
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
             "-N ".   
             "-I '".$this->_customRemotePath."app,".
                    $this->_customRemotePath."downloader,".
                    $this->_customRemotePath."errors,".
                    $this->_customRemotePath."includes,".
                    $this->_customRemotePath."js,".
                    $this->_customRemotePath."lib,".
                    $this->_customRemotePath."pkginfo,".
                    $this->_customRemotePath."shell,".
                    $this->_customRemotePath."skin' " .
             "--user='".$this->_storeObject->getCustomLogin()."' ".
             "--password='".$this->_storeObject->getCustomPass()."' ".
             "".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath."";
        exec($command, $output);
        //$message = var_export($output, true);

        unset($output);
        
        /**
         * TODO: validate output
         */
        
        return true;
    }

    public function checkDatabaseDump(){
        $command = "wget --spider ".$this->_customHost.":".$this->_customPort."".$this->_customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_storeObject->getCustomLogin()."' ".
            "--password='".$this->_storeObject->getCustomPass()."' ".
            "".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath." | grep 'SIZE'";
        exec($command,$output);

        //$message = var_export($output, true);

        foreach ($output as $out) {
            if (substr($out, 0, 8) == '==> SIZE') {
                $sqlSizeInfo = explode(' ... ', $out);
            }
        }

        /*if(isset($sqlSizeInfo[1])){
            //$log->log($sqlSizeInfo[1], LOG_DEBUG);
        }*/

       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){                       
            $this->_errorMessage = 'Couldn\'t find sql data file.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        unset($output);

        if ($sqlSizeInfo[1] > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big.';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        return true;
    }
    
    public function downloadDatabase(){
        
        $command = "wget  ".$this->_customHost.":".$this->_customPort."".$this->_customSql." ".
            "--passive-ftp ".
            "-N ".  
            "--user='".$this->_storeObject->getCustomLogin()."' ".
            "--password='".$this->_storeObject->getCustomPass()."' ".
            "".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath." ";
        exec($command,$output);
        //$message = var_export($output, true);
        
        unset($output);
        
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
        
        //download file
        $command = "wget  ".$this->_customHost.":".$this->_customPort."".$this->_customFile." ".
            "--passive-ftp ".
            "-N ".  
            "--user='".$this->_storeObject->getCustomLogin()."' ".
            "--password='".$this->_storeObject->getCustomPass()."' ".
            "".$this->_customHost.":".$this->_customPort."".$this->_customRemotePath." ";
        exec($command,$output);
        //$message = var_export($output, true);
        unset($output);
        
        /*TODO: validate output, that file really existed */
        
        
        //unpack to temp location
        exec('mkdir -p temporarystoredir/');
        
        
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        
        /**
         * Get filename out of path, 
         * becasue we have only downloaded file without filepath 
         */
        $pathinfo  = pathinfo($this->_customFile);
        exec('tar -zxf '.$pathinfo['basename'].' -C temporarystoredir/');
        
        //locate mage file 
        $output = array();
        $mageroot = '';
        exec('find -L -name Mage.php',$output);      
        
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
        $command = 'sudo mv '.$mageroot.'/* '.$mageroot.'/.??* .';
        exec($command,$output);
        unset($output);
        
        return true;
    }
    
    /*TODO: maybe methods validateFileExist and validateFileSize ? */
    
}
