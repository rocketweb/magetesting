<?php

/* revision classes might need abstract clas for overall repository handling (like creating one within instance */

class Application_Model_Task_Revision_Commit 
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
        
    }

    protected function _commit() {
        
    }

    protected function _push() {
        
    }

}
