<?php

class Application_Model_ServerMapper {

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
            $this->setDbTable('Application_Model_DbTable_Server');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Server $server)
    {
        $data = $server->__toArray();
        if (null === ($id = $server->getId())) {
            unset($data['id']);
            $server->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $server;
    }

    public function find($id, Application_Model_Server $server)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $server->setId($row->id)
               ->setName($row->name)
               ->setDescription($row->description)
               ->setDomain($row->domain)
               ->setIp($row->ip);
        return $server;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('id = ?' => $id));
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Server();
            $entry->setId($row->id)
            ->setName($row->name)
            ->setDescription($row->description)
            ->setDomain($row->domain)
            ->setIp($row->ip);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getKeys() {

        $temp = array();
        foreach ($this->fetchAll() as $r) {
            $temp[] = $r->getId();
        }
        return $temp;

    }

}
