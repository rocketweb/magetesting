<?php

class Application_Model_Task_Extension_Install 
extends Application_Model_Task_Extension
implements Application_Model_Task_Interface {

    protected $_extensionObject=  '';
    
    private $db;
    private $config;
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
        
        $extensionModel = new Application_Model_Extension();
        $extensionModel->find($queueElement->getExtensionId());
        $this->_extensionObject = $extensionModel;
        $this->logger = $this->_getLogger();
                
    }
    
    public function process() {
               
        $this->_updateStatus('installing-extension');
        
        //get instance data
        $magentoVersion = $this->_versionObject->getVersion();
        $sampleDataVersion = $this->_versionObject->getSampleDataVersion();
        
        //first check if we have that file
        $this->_checkPackage();
        
        //untar extension to instance folder
        $this->_install();
        
        //clear instance cache
        $this->_clearInstanceCache();
        
        //set extension as installed
        $this->_updateStatus('ready');        
                
    }

    protected function _checkPackage() {
        if (!file_exists($this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/'.$this->_extensionObject->getFileName())){
            $message = 'Extension file for '.$this->_extensionObject->getName().' could not be found';
            $this->_updateStatus('error',$message);
            return false;
        } 
    }

    protected function _install() {
        exec('tar -zxvf '.
            $this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/'.$this->_extensionObject->getFileName().
            ' -C '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_instanceObject->getDomain()
        ,$output);
        
        //output contains unpacked files list, so it should never be empty if unpacking suceed
        if (count($output)==0){
            $message = 'There was an error while installing extension '.$this->_extensionObject->getName();
            $this->_updateStatus('error',$message);
            return false;
        }
        $this->logger->log(var_export($output,true),LOG_DEBUG);
        unset($output);
    }

}
