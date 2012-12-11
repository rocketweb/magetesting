<?php

class Application_Model_Transport_Ftp extends Application_Model_Transport {
          
    protected $_customHost = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 
    
    public function setup(Application_Model_Instance &$instance){
        
        $this->_instanceObject = $instance;
        
        parent::setup($instance);
        $this->_prepareCustomVars($instance);
    }
    
    public function checkProtocolCredentials(){
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
       
        //SQL
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $this->_instanceObject->getCustomSql();
        if(substr($customSql,0,1)=="/"){
            $customSql = substr($customSql,1);
        }
        
        $this->_customSql = $customSql;
        
        //FILE
         //make sure sql file path does not contain slash at the beginning       
        $customFile = $this->_instanceObject->getCustomFile();
        if(substr($customFile,0,1)=="/"){
            $customFile = substr($customFile,1);
        }
        
        $this->_customFile = $customFile;
        
        return true;
    }
    
    public function downloadFilesystem(){
        
        if ($this->_instanceObject->getCustomFile()!=''){
            return $this->_downloadAndUnpack();
        } else {
            return $this->_downloadInstanceFiles();
        }

    }
    
    /* todo: make this protected */
    protected function _downloadInstanceFiles(){
        //do a sample connection, and check for index.php, if it works, start fetching
        $command = "wget --spider ".$this->_customHost.$this->_customRemotePath."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'";
        exec($command, $output);
        //$message = var_export($output, true);
        //$log->log($command."\n" . $message, LOG_DEBUG);

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
        //$message = var_export($output, true);

        unset($output);
        
        /**
         * TODO: validate output
         */
        
        return true;
    }

    public function checkDatabaseDump(){
        $command = "wget --spider ".$this->_customHost.$this->_customSql." 2>&1 ".
            "--passive-ftp ".
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." | grep 'SIZE'";
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
    
    public function downloadDatabase(){
        
        $command = "wget  ".$this->_customHost.$this->_customSql." ".
            "--passive-ftp ".
            "-N ".  
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." ";
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
        $command = "wget  ".$this->_customHost.$this->_customFile." ".
            "--passive-ftp ".
            "-N ".  
            "--user='".$this->_instanceObject->getCustomLogin()."' ".
            "--password='".$this->_instanceObject->getCustomPass()."' ".
            "".$this->_customHost.$this->_customRemotePath." ";
        exec($command,$output);
        //$message = var_export($output, true);
        unset($output);
        
        /*TODO: validate output, that file really existed */
        
        
        //unpack to temp location
        exec('mkdir -p temporaryinstancedir/');
        
        
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        
        /**
         * Get filename out of path, 
         * becasue we have only downloaded file without filepath 
         */
        $pathinfo  = pathinfo($this->_customFile);
        exec('tar -zxf '.$pathinfo['basename'].' -C temporaryinstancedir/');
        
        //locate mage file 
        $output = array();
        $mageroot = '';
        exec('find -L -name Mage.php',$output);      
        
        /* no matchees found */
        if ( count($output) == 0 ){
            return false;
        }
        
        foreach ($output as $line){
          if(substr($line,-13) == '/app/Mage.php'){
            $mageroot = substr($line,0,strpos($line,'/app/Mage.php'));
            echo $mageroot;
            break;
          }
        }
        
        /* no /app/Mage.php found */
        if ($mageroot == ''){
            return false;
        }

        /* move files from unpacked dir into our instance location */
        $output = array();
        $command = 'sudo mv '.$mageroot.'/* '.$mageroot.'/.??* .';
        exec($command,$output);
        unset($output);
        
        return true;
    }
    
    /*TODO: maybe methods validateFileExist and validateFileSize ? */
    
}
