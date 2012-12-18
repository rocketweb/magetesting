<?php 

class Application_Model_Transport_Ssh
extends Application_Model_Transport {

    protected $_customHost = '';
    protected $_customRemotePath = '';
    protected $_customFile = '';
    protected $_customSql = ''; 

    private $_connection;

    public function setup(Application_Model_Store &$store){

        $this->_storeObject = $store;

        parent::setup($store);

        /*TODO: replace 22 with given port*/
        $this->_connection = ssh2_connect($store->getCustomHost(), 22);
        ssh2_auth_password($this->_connection, $store->getCustomLogin(), $store->getCustomPassword());

        /*TODO: execute this somewhere, closeConnection() function? */
        //fclose($stream);

        $this->_prepareCustomVars($store);
    }

    public function checkProtocolCredentials(){

        if (!$this->_connection){
            throw new Application_Model_Transport_Exception('Checking ssh credentials failed');
        }
        else return true;
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

        //PATH
        $customRemotePath = $this->_storeObject->getCustomRemotePath();
        //make sure remote path containts slash at the end
        if(substr($customRemotePath,-1)!="/"){
            $customRemotePath .= '/';
        }

        //make sure remote path does not contain slash at the beginning       
        $customRemotePath = ltrim($customRemotePath,'/');
        
        $this->_customRemotePath = $customRemotePath;

        //SQL
         //make sure sql file path does not contain slash at the beginning       
        $customSql = $this->_storeObject->getCustomSql();
        $customSql = ltrim($customSql,'/');

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
            return $this->_downloadInstanceFiles();
        }

    }

    protected function _downloadInstanceFiles(){

        $components = count(explode('/',$this->_customRemotePath))-1;

        /**
         * the switch is used to not ask for /yes/no about adding host to known hosts
         */
        exec('set -xv');
        $command = 'sshpass -p'.$this->_storeObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
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
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }

        if ($sqlSizeInfo > $this->_sqlFileLimit){
            $this->_errorMessage = 'Sql file is too big';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
        }
        
        return true;
    }

    public function downloadDatabase(){
        $components = count(explode('/',$this->_customSql))-1;

        exec('set -xv');
        $command = 'sshpass -p'.$this->_storeObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' "tar -zcf - '.$this->_customSql.'"'
                .' | sudo tar -xzvf - --strip-components='.$components.' -C .';
        exec($command,$output);

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

        /* Download file*/
        /* TODO: determine filetype and use correct unpacker between gz,zip,tgz */
        $command = 'sshpass -p'.$this->_storeObject->getCustomPass()
                .' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no '
                .$this->_storeObject->getCustomLogin().'@'.trim($this->_customHost,'/')
                .' "cat '.$this->_customFile.'"'
                .' | sudo tar -xzvf - -C .';
        exec($command,$output);
                     
        //locate mage file 
        $output = array();
        $mageroot = '';
        exec('find -L -name Mage.php',$output);      

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
        $output = array();
        $command = 'sudo mv '.$mageroot.'/* '.$mageroot.'/.??* .';
        exec($command,$output);
        unset($output);

        return true;
    }

}
