<?php

class Application_Model_ExtensionQueueMapper {

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
            $this->setDbTable('Application_Model_DbTable_ExtensionQueue');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_ExtensionQueue $extensionQueue)
    {
        $data = array(
            'id' => $extensionQueue->getId(),
            'queue_id' => $extensionQueue->getQueueId(),
            'status' => $extensionQueue->getStatus(),
            'user_id' => $extensionQueue->getUserId(),
            'extension_id' => $extensionQueue->getExtensionId(),
        );

        if (null === ($id = $extensionQueue->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

    }

    public function find($id, Application_Model_ExtensionQueue $extensionQueue)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extensionQueue->setId($row->id)
        ->setQueueId($row->name)
        ->setStatus($row->file_name)
        ->setUserId($row->from_version)
        ->setExtensionId($row->to_version);
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
            $entry = new Application_Model_ExtensionQueue();
            $entry->setId($row->id)
            ->setQueueId($row->name)
            ->setStatus($row->file_name)
            ->setUserId($row->from_version)
            ->setExtensionId($row->to_version);
            $entries[] = $entry;
        }
        return $entries;
    }
}