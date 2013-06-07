<?php

class Application_Model_Task_Extension_Opensource 
extends Application_Model_Task_Extension 
implements Application_Model_Task_Interface {

    protected $_extensionObject=  '';
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
        
        $extensionModel = new Application_Model_Extension();
        $extensionModel->find($queueElement->getExtensionId());
        $this->_extensionObject = $extensionModel;
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
               
        $this->_updateStoreStatus('installing-extension');
        
        //get store data
        $magentoVersion = $this->_versionObject->getVersion();
        $sampleDataVersion = $this->_versionObject->getSampleDataVersion();
        
        //first check if we have that file
        $this->_checkPackage();

        $storeExtensionModel = new Application_Model_StoreExtension();
        $storeExtensionModel->fetchStoreExtension($this->_storeObject->getId(), $this->_extensionObject->getId());
        // set processing status
        $storeExtensionModel->setStatus('processing')->save();

        //untar opensourced extension to store folder
        $this->_install();
        
        // set ready status
        $storeExtensionModel->setStatus('ready')->save();
        
        //clear store cache
        $this->_clearStoreCache();
                        
    }

    protected function _checkPackage() {
        if (!file_exists($this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/open/'.$this->_extensionObject->getExtension())){
            $message = 'Extension file for '.$this->_extensionObject->getName().' could not be found';
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        } 
    }

    protected function _install() {
        $output='';

        $this->logger->log('Unpacking and installing extension.', Zend_Log::INFO);

        /* Regardless of price, install decoded version */
        exec('tar -zxvf '.
            $this->config->extension->directoryPath.'/'.$this->_versionObject->getEdition().'/open/'.$this->_extensionObject->getExtension().
            ' -C '.$this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_storeObject->getDomain()
        ,$output);

        //output contains unpacked files list, so it should never be empty if unpacking suceed
        $this->logger->log(var_export($output,true),Zend_Log::DEBUG);
        if (count($output)==0){
            
            $message = 'There was an error while opening source of extension '.$this->_extensionObject->getName();
            $this->logger->log($message, Zend_Log::EMERG);
            throw new Application_Model_Task_Exception($message);
        }      
        
    }    

}
