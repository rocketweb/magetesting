<?php

class Application_Model_QueueMapper {

    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Queue');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Queue $queue)
    {
        $data = array(
            'id' => $queue->getId(),
            'store_id' => $queue->getStoreId(),
            'status' => $queue->getStatus(),
            'user_id' => $queue->getUserId(),
            'extension_id' => $queue->getExtensionId(),
            'task' => $queue->getTask(),
            'task_params' => $queue->getTaskParams(false),
            'retry_count' => $queue->getRetryCount(),
            'server_id' => $queue->getServerId(),
            'parent_id' => $queue->getParentId(),
            'added_date' => $queue->getAddedDate(),
        );
        
        if (!($id = (int)$queue->getId())) {
            unset($data['id']);
            unset($data['retry_count']);
            $queue->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $queue;

    }

    public function find($id, Application_Model_Queue $queue)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $queue->setId($row->id)
        ->setStoreId($row->store_id)
        ->setStatus($row->status)
        ->setUserId($row->user_id)
        ->setExtensionId($row->extension_id)
        ->setTask($row->task)
        ->setTaskParams($row->task_params,false)
        ->setRetryCount($row->retry_count)
        ->setServerId($row->server_id)
        ->setParentId($row->parent_id)
        ->setAddedDate($row->added_date);
        return $queue;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete($id);
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Queue();
            $entry->setId($row->id)
            ->setStoreId($row->store_id)
            ->setStatus($row->status)
            ->setUserId($row->user_id)
            ->setExtensionId($row->extension_id)
            ->setTask($row->task)
            ->setTaskParams($row->task_params,false)
            ->setRetryCount($row->retry_count)
            ->setServerId($row->server_id)
            ->setParentId($row->parent_id)
            ->setAddedDate($row->added_date);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function getForServer($worker_id,$type){
        $row = $this->getDbTable()->getForServer($worker_id,$type);
       if ($row){ 
            $entry = new Application_Model_Queue();
            $entry->setId($row->id)
            ->setStoreId($row->store_id)
            ->setStatus($row->status)
            ->setUserId($row->user_id)
            ->setExtensionId($row->extension_id)
            ->setTask($row->task)
            ->setTaskParams($row->task_params,false)
            ->setRetryCount($row->retry_count)
            ->setServerId($row->server_id)
            ->setParentId($row->parent_id)
            ->setAddedDate($row->added_date);
            return $entry;
       } else {
           return false;
       }
    }
    
    public function getParentIdForExtensionInstall($store_id){
        return $this->getDbTable()->getParentIdForExtensionInstall($store_id);
    }
    
    public function countForStore($storeId){
        return $this->getDbTable()->countForStore($storeId);
    }
    
    public function findPositionByName($storeName)
    {
        return $this->getDbTable()
                    ->findPositionByName($storeName);
        
    }
    
    public function alreadyExists($taskType,$storeId,$extensionId=NULL,$serverId=1){
        return $this->getDbTable()
                    ->alreadyExists($taskType,$storeId,$extensionId,$serverId);
    }
    
    public function getNextForStore($storeId){
        return $this->getDbTable()->getNextForStore($storeId);
    }
}