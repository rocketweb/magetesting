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
            'server_id' => $queue->getServerId(),
            'parent_id' => $queue->getParentId(),
        );

        if (null === ($id = $queue->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

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
        ->setServerId($row->server_id)
        ->setParentId($row->parent_id);
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
            ->setServerId($row->server_id)
            ->setParentId($row->parent_id);
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
            ->setServerId($row->server_id)
            ->setParentId($row->parent_id);
            $entries[] = $entry;
        }
        return $entries;
    }
}