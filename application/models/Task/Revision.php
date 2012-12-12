<?php

class Application_Model_Task_Revision 
extends Application_Model_Task
 {
    
    public function setup(Application_Model_Queue &$queueElement){
        parent::setup($queueElement);
    }
    
    public function _updateRevisionCount($modifier){
        
        $currentRevision = (int)$this->_instanceObject->getRevisionCount();
        
        $operation = substr($modifier,0,1);
        
        if ($operation=='+'){
            $nextRevision = $currentRevision + substr($modifier,1);
        } elseif ($operation=='-') {
            $nextRevision = $currentRevision - substr($modifier,1);
        }
        
        $this->db->update('instance', array('revision_count' => $nextRevision), 'id=' . $this->_instanceObject->getId());
        $this->_instanceObject->setRevisionCount($nextRevision);
        
    }
    
}
        
