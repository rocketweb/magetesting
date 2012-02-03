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
                'edition'        => $queue->getEdition(),
                'status'         => $queue->getStatus(),
                'version_id'     => $queue->getVersionId(),
                'user_id'        => $queue->getUserId(),
                'domain'         => $queue->getDomain(),
                'instance_name'  => $queue->getInstanceName()
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
                ->setEdition($row->edition)
                ->setStatus($row->status)
                ->setVersionId($row->version_id)
                ->setUserId($row->user_id)
                ->setDomain($row->domain)
                ->setInstanceName($row->instance_name);
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
                    ->setEdition($row->edition)
                    ->setStatus($row->status)
                    ->setVersionId($row->version_id)
                    ->setUserId($row->user_id)
                    ->setDomain($row->domain)
                    ->setInstanceName($row->instance_name);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getAll()
    {
        return $this->getDbTable()->getAllJoinedWithVersions();
    }

    public function changeStatusToClose($queue)
    {
        if($queue->getUserId() AND $queue->getDomain()) {
            $this->getDbTable()->changeStatusToClose(
                    $queue->getUserId(),
                    $queue->getDomain()
            );
        }
    }

    public function getAllForUser( $user_id )
    {
        return $this->getDbTable()->findAllByUser( $user_id );
    }

    public function countUserInstances( $user_id )
    {
        $data = $this->getDbTable()
                     ->countUserInstances( $user_id )
                     ->current();

        return (int)$data->instances;
    }

}