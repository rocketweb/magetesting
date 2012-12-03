<?php

class Application_Model_Task_Extension 
extends Application_Model_Task {
    private $db;
    private $config;
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function setup(Application_Model_Queue &$queueElement){
        
        parent::setup($queueElement);
    }
       
}
        