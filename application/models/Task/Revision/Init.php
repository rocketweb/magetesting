<?php

class Application_Model_Task_Revision_Init
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {
   
    public function process(Application_Model_Queue &$queueElement = null) {        
        $this->_updateStatus('committing-revision');
        $this->_prepareGitIgnore();
        $this->_createDeploymentDir();
        $this->_initRepo();
        $this->_updateStatus('ready');
    }

    /**
     * This method should take place AFTER magento has been successfully installed
     */
     
    protected function _initRepo() {
        $this->logger->log('Initing git repository.', Zend_Log::INFO);
        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        exec('git init');
        
        chdir($startCwd);
    }
    
    protected function _prepareGitIgnore(){
        $data = "".
        "var/".PHP_EOL.
        "media/".PHP_EOL.
        "";
        
        if (!file_exists($this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/.gitignore')){
            exec('touch '.$this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/.gitignore');
        }
        
        file_put_contents($this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/.gitignore', $data);
    }
    
    protected function _createDeploymentDir(){
        $this->logger->log('Creating directory for deployment packages.', Zend_Log::INFO);
        
        $deploymentPath = $this->_instanceFolder.'/'.$this->_instanceObject->getDomain().
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
