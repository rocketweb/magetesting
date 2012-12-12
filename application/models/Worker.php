<?php

class Application_Model_Worker {
    
    public function __construct(&$config, &$db) {
        $this->config = $config;
        $this->db = $db;
    }
    
    public function work(Application_Model_Queue $queueElement){
        
        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $classSuffix = $filter->filter($queueElement->getTask());
        
        $className = 'Application_Model_Task_'.$classSuffix; 
 
        $newRetryCount = $queueElement->getRetryCount() + 1;
        $this->db->update('queue', array('retry_count' => $newRetryCount), 'id = ' . $queueElement->getId());
        $queueElement->setRetryCount($newRetryCount)->save();
        
        try {
            $customTaskModel = new $className($this->config,$this->db);       
            $customTaskModel->setup($queueElement);
            $this->db->update('queue', array('status' => 'processing'), 'id = ' . $queueElement->getId());
            $customTaskModel->process();

            /* TODO: remove this if after all exceptions are implemented on errors */
            if ($queueElement->getStatus()=='ready'){
                $this->db->update('queue', array('parent_id' => '0'), 'parent_id = ' . $queueElement->getId());
                $this->db->delete('queue', array('id=' . $queueElement->getId()));
                
                $this->db->update('instance', array('status' => 'ready'), 'id = ' . $queueElement->getInstanceId());
            }
        
        } catch (Exception $e){
            $this->db->update('queue', array('status' => 'pending'), 'id = ' . $queueElement->getId());
            $this->db->update('instance', array('error_message' => $e->getMessage()), 'id = ' . $queueElement->getInstanceId());
        }
    }
}