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
            'instance_id' => $queue->getInstanceId(),
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
        
        if (null === ($id = $queue->getId())) {
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
        ->setInstanceId($row->instance_id)
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
            ->setInstanceId($row->instance_id)
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
        $resultSet = $this->getDbTable()->getForServer($worker_id,$type);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Queue();
            $entry->setId($row->id)
            ->setInstanceId($row->instance_id)
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
    
    public function getParentIdForExtensionInstall($instance_id){
        return $this->getDbTable()->getParentIdForExtensionInstall($instance_id);
    }
    
}