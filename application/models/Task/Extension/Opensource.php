<?php

class Application_Model_Task_Extension_Opensource 
extends Application_Model_Task_Extension 
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

    protected function _replaceFiles() {
        
    }

}
