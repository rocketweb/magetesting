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
        $this->cli('git')->init()->call();

        chdir($startCwd);
    }
    
    protected function _prepareGitIgnore(){
        $data = "".
        "var/".PHP_EOL.
        "media/".PHP_EOL.
        "";
        $gitignore = $this->_storeFolder.'/'.$this->_storeObject->getDomain().'/.gitignore';
        if (!file_exists($gitignore)){
            $file = $this->cli('file');
            $file->create($gitignore, $file::TYPE_FILE)->call();
        }
        
        file_put_contents($gitignore, $data);
    }
    
    protected function _createDeploymentDir(){
        $this->logger->log('Creating directory for deployment packages.', Zend_Log::INFO);
        
        $deploymentPath = $this->_storeFolder.'/'.$this->_storeObject->getDomain().
                '/var/deployment/';
        
        
        if (!file_exists($deploymentPath) || !is_dir($deploymentPath)){
            
            $startCwd = getcwd();
        
            $file = $this->cli('file');
            $file->create($deploymentPath, $file::TYPE_DIR)->call();

            $rules = "Order allow,deny\n".
            "Allow from all";
        
            file_put_contents($deploymentPath.'/.htaccess', $rules); 
            
            chdir($startCwd);
        }       
    }
}
