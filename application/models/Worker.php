<?php

class Application_Model_Worker {

    public function __construct(&$config, &$db, &$log) {
        $this->config = $config;
        $this->db = $db;
        $this->log = $log;
    }

    public function work(Application_Model_Queue $queueElement){

        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $classSuffix = $filter->filter($queueElement->getTask());

        $className = 'Application_Model_Task_'.$classSuffix; 

        $newRetryCount = $queueElement->getRetryCount() + 1;
        $this->db->update('queue', array('retry_count' => $newRetryCount), 'id = ' . $queueElement->getId());
        $queueElement->setRetryCount($newRetryCount)->save();
        $customTaskModel = new $className($this->config,$this->db);

        
        try {
            $customTaskModel->setup($queueElement);
            $this->db->update('queue', array('status' => 'processing'), 'id = ' . $queueElement->getId());
            $queueElement->setStatus('processing');
            $customTaskModel->process();

            $this->db->update('queue', array('parent_id' => '0'), 'parent_id = ' . $queueElement->getId());
            $this->db->delete('queue', array('id=' . $queueElement->getId()));

            /** 
             * if no other tasks are present for this store, 
             * update store status to ready
             * Otherwise, update status so we know what's in queue
             */
            $queueModel = new Application_Model_Queue();
            if(!$queueModel->countForStore($queueElement->getStoreId())){
                $this->db->update('store', array('status' => 'ready'), 'id = ' . $queueElement->getStoreId());
            } else {
                /**
                 * update store status to new task type
                 */
                $storeModel = new Application_Model_Store();
                $nextElement = $queueModel->getNextForStore($queueElement->getStoreId());
                $newStatus = $storeModel->getStatusFromTask($nextElement->getTask());
                $this->db->update('store', 
                        array('status' => $newStatus), 
                        'id = ' . $queueElement->getStoreId()
                        );
            }
        } catch (Application_Model_Task_Exception $e){
            $this->db->update('queue', array('status' => 'pending'), 'id = ' . $queueElement->getId());
            $this->db->update('store', array('error_message' => $e->getMessage(),'status' => 'error'), 'id = ' . $queueElement->getStoreId());
        } catch (Exception $e){
            $log = $this->log;
            $log->log($e->getMessage(), Zend_Log::CRIT, $e->getTraceAsString());
        }
        
    }
}