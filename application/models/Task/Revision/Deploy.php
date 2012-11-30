<?php

class Application_Model_Task_Revision_Deploy 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        
    }

    protected function _deploy() {
        
    }

}
