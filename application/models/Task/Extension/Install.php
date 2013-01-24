<?php

class Application_Model_Task_Extension_Install 
extends Application_Model_Task_Extension
implements Application_Model_Task_Interface {

    protected $_extensionObject=  '';
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
        
        $extensionModel = new Application_Model_Extension();
        $extensionModel->find($queueElement->getExtensionId());
        $this->_extensionObject = $extensionModel;

        $this->_dbuser = $this->_userObject->getLogin();
        $this->_domain = $this->_storeObject->getDomain();
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
               
        $this->_updateStoreStatus('installing-extension');
        
        //get store data
        $magentoVersion = $this->_versionObject->getVersion();
        $sampleDataVersion = $this->_versionObject->getSampleDataVersion();
        
        //first check if we have that file
        $this->_checkPackage();
        
        //untar extension to store folder
        $this->_install();
        
        //clear store cache
        $this->_clearStoreCache();
                        
    }

    protected function _checkPackage() {
        
        //if extension is commercial, check existence of encoded file, 
        if ($this->_extensionObject->getPrice() > 0 ){
            if (trim($this->_extensionObject->getExtensionEncoded())==''){
                $message = 'Extension price has been set to greater than 0 but no encoded package has been set for selected extension';
                $this->logger->log($message, Zend_Log::EMERG);
                throw new Application_Model_Task_Exception($message);
            }
            
            if (!file_exists($this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/encoded/'.$this->_extensionObject->getExtensionEncoded())){
                $message = 'Extension file for '.$this->_extensionObject->getName().' could not be found';
                $this->logger->log($message, Zend_Log::EMERG);
                throw new Application_Model_Task_Exception($message); 
            } 
        } else {
            
            if (trim($this->_extensionObject->getExtension())==''){
                $message = 'No package has been set for selected extension';
                $this->logger->log($message, Zend_Log::EMERG);
                throw new Application_Model_Task_Exception($message);
            }
            
            if (!file_exists($this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/open/'.$this->_extensionObject->getExtension())){
                $message = 'Extension file for '.$this->_extensionObject->getName().' could not be found';
                $this->logger->log($message, Zend_Log::EMERG);
                throw new Application_Model_Task_Exception($message);
            } 
        }
        
        
    }

    protected function _install() {
        $output='';

        $this->logger->log('Unpacking and installing extension.', Zend_Log::INFO);

        $tmp = '/home/'.$this->config->magento->userprefix . $this->_userObject->getLogin().'/extensiontmp';
        $tmpExtensionDir = $tmp.
                            '/'.$this->config->magento->userprefix . $this->_userObject->getLogin() . 
                            '/'.$this->_storeObject->getDomain().
                            '/'.$this->_extensionObject->getId();
        mkdir($tmpExtensionDir, 0777, true);
        
        if ($this->_extensionObject->getPrice() > 0 ){
            $command = 'tar -zxvf '.$this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/encoded/'.$this->_extensionObject->getExtensionEncoded().' -C '. $tmpExtensionDir
            ;
            
        } else {
            $command = 'tar -zxvf '.
                $this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/open/'.$this->_extensionObject->getExtension().
                ' -C '.$tmpExtensionDir;

        }
        
        exec($command, $output);
        //output contains unpacked files list, so it should never be empty if unpacking suceed
        $message = var_export($output,true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        if (count($output)==0){
            
            $message = 'There was an error while installing extension '.$this->_extensionObject->getName();
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        }  
        
        unset($output);
        
        //set permissions on files in tmp directory
        $command = 'chown -R '.$this->config->magento->userprefix . $this->_userObject->getLogin().':'.$this->config->magento->userprefix . $this->_userObject->getLogin().' '.$tmpExtensionDir.'';
        exec($command,$output);
        $message = var_export($output,true);
        $this->logger->log($command."\n".$message,Zend_Log::DEBUG);
        unset($output);
        
        //set permission on dirs 
        $command = 'find '.$tmpExtensionDir.' -type d -print | xargs chmod 755';
        exec($command,$output);
        $message = var_export($output,true);
        $this->logger->log($command."\n".$message,Zend_Log::DEBUG);
        unset($output);
        
        //set permission on files
        $command = 'find '.$tmpExtensionDir.' -type f -print | xargs chmod 644';
        exec($command,$output);
        $message = var_export($output,true);
        $this->logger->log($command."\n".$message,Zend_Log::DEBUG);
        unset($output);
        
        //move files from $tmpExtensionDir to store folder
        $command = 'cp -rp '.$tmpExtensionDir.'/* '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_storeObject->getDomain().'/ ';
        exec($command,$output);
        $message = var_export($output,true);
        $this->logger->log($command."\n".$message,Zend_Log::DEBUG);
        unset($output);
        
        //remove tmpextensiondir
        exec('sudo rm -R '.$tmp);
    }    

}
