<?php

class Application_Model_Task_Revision_Init
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {
   
    public function process(Application_Model_Queue $queueElement = null) {        
        $this->_updateStoreStatus('committing-revision');
        $this->_prepareGitIgnore();
        $this->_createDeploymentDir();
        $this->_initRepo();
    }

    /**
     * This method should take place AFTER magento has been successfully installed
     */
     
    protected function _initRepo() {
        $this->logger->log('Initing git repository.', Zend_Log::INFO);
        $startCwd = getcwd();
        
        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain());
        exec('git init');
        
        chdir($startCwd);
    }
    
    protected function _prepareGitIgnore(){
        $data = "".
        "var/".PHP_EOL.
        "media/".PHP_EOL.
        "";
        
        if (!file_exists($this->_storeFolder.'/'.$this->_storeObject->getDomain().'/.gitignore')){
            exec('touch '.$this->_storeFolder.'/'.$this->_storeObject->getDomain().'/.gitignore');
        }
        
        file_put_contents($this->_storeFolder.'/'.$this->_storeObject->getDomain().'/.gitignore', $data);
    }
    
    protected function _createDeploymentDir(){
        $this->logger->log('Creating directory for deployment packages.', Zend_Log::INFO);
        
        $deploymentPath = $this->_storeFolder.'/'.$this->_storeObject->getDomain().
                '/var/deployment/';
        
        
        if (!file_exists($deploymentPath) || !is_dir($deploymentPath)){
            
            $startCwd = getcwd();
        
            exec('mkdir -p '.$deploymentPath);
            
            $rules = "Order allow,deny\n".
            "Allow from all";
        
            file_put_contents($deploymentPath.'/.htaccess', $rules); 
            
            chdir($startCwd);
        }       
    }
}
