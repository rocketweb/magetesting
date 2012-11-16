<?php

class Application_Model_InstanceExtensionMapper{
    
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
            $this->setDbTable('Application_Model_DbTable_InstanceExtension');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_InstanceExtension $instanceExtension)
    {
        $data = $instanceExtension->__toArray();

        if (null === ($id = $instanceExtension->getId())) {
            unset($data['id']);
            $data['added_date'] = date('Y-m-d H:i:s');
            $instanceExtension->setAddedDate($data['added_date']);  
            $instanceExtension->setId($this->getDbTable()->insert($data));
        } else {
            unset($data['added_date']);
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $instanceExtension;
    }

    public function find($id, Application_Model_InstanceExtension $instanceExtension)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $instanceExtension->setId($row->id)
            ->setInstanceId($row->instance_id)
            ->setExtensionId($row->extension_id)
            ->setAddedDate($row->added_date);

        return $instanceExtension;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_InstanceExtension();
            $entry->setId($row->id)
                  ->setInstanceId($row->instance_id)
                  ->setExtensionId($row->extension_id)
                  ->setAddedDate($row->added_date);

            $entries[] = $entry;
        }
        return $entries;
    }
    
}
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
