<?php 

class Application_Model_Transport_Ssh
extends Application_Model_Transport {
    
    protected $_customHost = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 
    
    private $_connection;
    
    public function setup(Application_Model_Store &$instance){
        
        $this->_instanceObject = $instance;
        
        parent::setup($instance);
        
	/*TODO: replace 22 with given port*/
        $this->_connection = ssh2_connect($instance->getCustomHost(), 22);
        ssh2_auth_password($this->_connection, $instance->getCustomLogin(), $instance->getCustomPassword());
        
        /*TODO: execute this somewhere, closeConnection() function? */
        //fclose($stream);
        
        $this->_prepareCustomVars($instance);
    }
    
    public function checkProtocolCredentials(){
        
        if (!$this->_connection){
            return false;
        }
        else return true;
    }
    
    /* TODO: move to parent and rewrite if necessary ? */
    protected function _prepareCustomVars(Application_Model_Store $instanceObject){
        //HOST
        $customHost = $this->_instanceObject->getCustomHost();
        //make sure custom host have slash at the end
        if(substr($customHost,-1)!="/"){
            $customHost .= '/';
        }
        
        //make sure remote path contains prefix:
        
        /*if(substr($customHost, 0, 6)!='ftp://'){
            $customHost = 'ftp://'.$customHost;
        }*/
        
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
            echo 'downandunpack';
            return $this->_downloadAndUnpack();
        } else {
            echo 'downloadinstance';
            return $this->_downloadInstanceFiles();
        }

    }
    
    protected function _downloadInstanceFiles(){
        
        $components = count(explode('/',$this->_customRemotePath))-1;
       
        /**
         * the switch is used to not ask for /yes/no about adding host to known hosts
         */
        exec('set -xv');
        $command = 'sshpass -p'.$this->_instanceObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_instanceObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' "tar -zcf - '.$this->_customRemotePath.' --exclude='.$this->_customRemotePath.'var --exclude='.$this->_customRemotePath.'media"'
                .' | sudo tar -xzvf - --strip-components='.$components.' -C .';
        exec($command,$output);
        
        /**
         * TODO: validate output
         */
        
        return true;
    }

    public function checkDatabaseDump(){
        
        if($output = ssh2_exec($this->_connection, 'du -b '.$this->_customSql.'')) {
            stream_set_blocking($output, true);
            $content = stream_get_contents($output);
        }
        
        // Since du should return something like '12345   filename.ext'
        $duParts = explode(' ',$content);
        $sqlSizeInfo = $duParts[0];

       //limit is in bytes!
        if ($duParts[0] == 'du:' && $duParts[1] == 'cannot' && $duParts[1]=='access'){                       
            $this->_errorMessage = 'Couldn\'t find sql data file, will not install queue element';
            return false;        
        }

        if ($sqlSizeInfo > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big';
            return false;  
        }
        
        return true;
    }
    
    public function downloadDatabase(){
        $components = count(explode('/',$this->_customSql))-1;
        
        exec('set -xv');
        $command = 'sshpass -p'.$this->_instanceObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_instanceObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' "tar -zcf - '.$this->_customSql.'"'
                .' | sudo tar -xzvf - --strip-components='.$components.' -C .';
        exec($command,$output);
        
        var_dump($output);
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
            return false;        
        }
        
        /* Download file*/
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        $command = 'sshpass -p'.$this->_instanceObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_instanceObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' "cat '.$this->_customFile.'"'
                .' | sudo tar -xzvf - -C .';
        exec($command,$output);
                     
        //locate mage file 
        $output = array();
        $mageroot = '';
        exec('find -L -name Mage.php',$output);      
        
        var_dump($output);
        /* no matchees found */
        if ( count($output) == 0 ){
            return false;
        }
        
        foreach ($output as $line){
          if(substr($line,-13) == '/app/Mage.php'){
            $mageroot = substr($line,0,strpos($line,'/app/Mage.php'));
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
    
    
    
}
