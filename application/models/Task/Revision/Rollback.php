<?php

class Application_Model_Task_Revision_Rollback 
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
        
        $this->_rollbackTo();
        
    }

    protected function _checkCredentials() {
        
    }

    protected function _rollbackTo() {
        
        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        exec('git add -A');
        
        $params = $this->_queueObject->getTaskParams();
       
        
        exec('git ');
        chdir($startCwd);
        
        $this->_updateStatus('ready');
        
    }

}
