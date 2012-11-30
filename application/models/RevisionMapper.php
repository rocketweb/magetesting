<?php

class Application_Model_RevisionMapper {

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
            $this->setDbTable('Application_Model_DbTable_Revision');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Revision $revision)
    {
        $data = $revision->__toArray();

        if (null === ($id = $revision->getId())) {
            unset($data['id']);
            $revision->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

        return $revision;
    }

    public function find($id, Application_Model_Revision $revision)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $revision->setId($row->id)
              ->setUserId($row->user_id)
                ->setInstanceId($row->instance_id)
                ->setExtensionId($row->extension_id)
                ->setType($row->type)
                ->setHash($row->hash)
                ->setComment($row->comment)
                ->setFilename($row->filename)
                ->setDbBeforeRevision($row->db_before_revision);
        
        return $revision;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete($id);
    }

    public function fetchAll($user_id=null)
    {
        $where = null;
        if(!$user_id) {
            $where = $this->getDbTable()->select()->where('user_id = ?', 0);
        }
        $resultSet = $this->getDbTable()->fetchAll($where);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Revision();
            $entry->setId($row->id)
                ->setUserId($row->user_id)
                ->setInstanceId($row->instance_id)
                ->setExtensionId($row->extension_id)
                ->setType($row->type)
                ->setHash($row->hash)
                ->setComment($row->comment)
                ->setFilename($row->filename)
                ->setDbBeforeRevision($row->db_before_revision);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getAllForInstance($instance_id)
    {
        $result = array();
        if((int)$instance_id) {
            $tmp = $this->getDbTable()->getAllForInstance($instance_id);
            if($tmp) {
                $result = $tmp;
            }
        }
        return $result;
    }
}