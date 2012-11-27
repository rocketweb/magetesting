<?php

class Application_Model_Task_Revision_Init
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    private $db;
    private $config;
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function process() {        
        $this->_prepareGitIgnore();
        $this->_initRepo();
        $this->_updateStatus('ready');
    }

    /**
     * This method should take place AFTER magento has been successfully installed
     */
     
    protected function _initRepo() {
        //init git repository
        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        exec('git init');
        
        exec('git add .');
        exec('git commit -m "Installed magento store (instance '.$this->_instanceObject->getDomain().').";');
        chdir($startCwd);
    }
    
    protected function _prepareGitIgnore(){
        $data = "".
        "var/cache/".PHP_EOL.
        "";
        
        file_put_contents($this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/.gitignore', $data);
    }

}
