<?php

class Application_Model_DevExtensionQueueMapper {

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
            $this->setDbTable('Application_Model_DbTable_DevExtensionQueue');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_DevExtensionQueue $extensionQueue)
    {
        $data = array(
            'id' => $extensionQueue->getId(),
            'instance_id' => $extensionQueue->getInstanceId(),
            'status' => $extensionQueue->getStatus(),
            'user_id' => $extensionQueue->getUserId(),
            'dev_extension_id' => $extensionQueue->getDevExtensionId(),
        );

        if (null === ($id = $extensionQueue->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

    }

    public function find($id, Application_Model_DevExtensionQueue $extensionQueue)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extensionQueue->setId($row->id)
        ->setInstanceId($row->instance_id)
        ->setStatus($row->status)
        ->setUserId($row->user_id)
        ->setDevExtensionId($row->dev_extension_id);
        return $extensionQueue;
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
            $entry = new Application_Model_DevExtensionQueue();
            $entry->setId($row->id)
            ->setInstanceId($row->instance_id)
            ->setStatus($row->status)
            ->setUserId($row->user_id)
            ->setDevExtensionId($row->dev_extension_id);
            $entries[] = $entry;
        }
        return $entries;
    }
}