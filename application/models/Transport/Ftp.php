<?php

class Application_Model_Transport_Ftp extends Application_Model_Transport {
          
    protected $_customHost = '';
    protected $_customRemotePath = '';
    protected $_customSql = '';
    protected $_errorMessage = '';
    
    public function setup(Application_Model_Instance &$instance){
        
        $this->_instanceObject = $instance;
        
        parent::setup($instance);
        $this->_prepareCustomVars($instance);
    }
    
    protected function _checkProtocolCredentials(){
        exec("wget --spider ".$this->_customHost." ".
             "--passive-ftp ".
             "--user='".$this->_instanceObject->getCustomLogin()."' ".
             "--password='".$this->_instanceObject->getCustomPass()."' ".
             "".$this->_customHost." 2>&1 | grep 'Logged in!'",$output);
               
        if (!isset($output[0])){
            return false;
        }
        return true;
    }
    
    protected function _prepareCustomVars(Application_Model_Instance $instanceObject){
        //HOST
        $customHost = $this->_instanceObject->getCustomHost();
        //make sure custom host have slash at the end
        if(substr($customHost,-1)!="/"){
            $customHost .= '/';
        }
        
        //make sure remote path contains prefix:
        
        if(substr($customHost, 0, 6)!='ftp://'){
            $customHost = 'ftp://'.$customHost;
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
        $this->_customRemotePath = $customRemotePath;
       
        //FILE
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $this->_instanceObject->getCustomSql();
        if(substr($customSql,0,1)=="/"){
            $customSql = substr($customSql,1);
        }
        
        $this->_customSql = $customSql;
        return true;
    }
    
    
    
    protected function _downloadInstanceFiles(){
        $log = $this->_getLogger();
         //echo "Copying package to target directory...\n";
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
             "--user='".$this->_instanceObject->getCustomLogin()."' ".
             "--password='".$this->_instanceObject->getCustomPass()."' ".
             "".$this->_customHost.$this->_customRemotePath."";
        exec($command, $output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);
        
        /**
         * TODO: validate output
         */
        
        return true;
    }

    protected function _checkDatabaseDump(){
        $log = $this->_getLogger();
        $command = "wget --spider ".$this->_customHost.$this->_customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'";
        exec($command,$output);

        $message = var_export($output, true);
        $log->log("\n".$message."\n" . $message, LOG_DEBUG);

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
            $this->_errorMessage = 'Couldn\'t find sql data file, will not install queue element';
            return false;        
        }
        unset($output);

        if ($sqlSizeInfo[1] > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big';
            return false;  
        }
        
        return true;
    }
    
    protected function _downloadDatabase(){
        $log = $this->_getLogger();
        
        $command = "wget  ".$this->_customHost.$this->_customSql." ".
            "--passive-ftp ".
            "-N ".  
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." ";
        exec($command,$output);
        $message = var_export($output, true);
        $log->log($command."\n" . $message, LOG_DEBUG);
        unset($output);
        
        /* TODO: validate if local and reomte size are correct */
    }
    
    public function getError(){
        return $this->_errorMessage;
    }
    
}