<?php

class Application_Model_Task_Extension_Opensource 
extends Application_Model_Task_Extension 
implements Application_Model_Task_Interface {

    public function process(Application_Model_Queue &$queueElement = null) {
        
        $this->_updateStatus('installing-extension');
        //process
        
        $this->_updateStatus('ready');
    }

    protected function _replaceFiles() {
        
    }

}
