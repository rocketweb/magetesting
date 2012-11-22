<?php

class Application_Model_ExtensionCategoryMapper {

    protected $_dbTable;

    protected $_error = '';
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
            $this->setDbTable('Application_Model_DbTable_ExtensionCategory');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_ExtensionCategory $object)
    {
        $data = $object->__toArray();

        if (null === ($id = $object->getId())) {
            $object->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $object;
    }

    public function find($id, Application_Model_ExtensionCategory $object)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        
        $row = $result->current();
        $object->setId($row->id)
               ->setName($row->name)
               ->setClass($row->class);
        return $object;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_ExtensionCategory();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setClass($row->class);
            $entries[] = $entry;
        }
        return $entries;
    }

}