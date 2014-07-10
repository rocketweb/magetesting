<?php

class Application_Model_Task_Extension_Conflict
    extends Application_Model_Task_Extension
    implements Application_Model_Task_Interface {

    protected $_extensionObject=  '';

    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
    }

    public function process(Application_Model_Queue $queueElement = null) {

        $this->_updateStoreStatus('extension-conflict');

        $this->_checkForConflicts(true);

    }
}
